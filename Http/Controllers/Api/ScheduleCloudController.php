<?php

namespace Modules\SimVector\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Http\Controllers\Api\FlightController;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Bid;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Models\User;
use App\Repositories\Criteria\WhereCriteria;
use App\Repositories\FlightRepository;
use App\Services\BidService;
use App\Services\FareService;
use App\Services\FlightService;
use App\Services\ImportExport\FlightImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\SimVector\Services\DataService;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;

/**
* class ApiController
* @package Modules\SimVector\Http\Controllers\Api
*/
class ScheduleCloudController extends Controller
{
    public function __construct(
        public DataService $dataService,
        public BidService $bidService,
        public FlightRepository $flightRepo,
        public FlightService $flightSvc,
        public FareService $fareSvc,
    )
    {}
    /**
    * Override the default /api/flights/search endpoint to inject SimVector flights
    *
    * @param Request $request
    *
    * @return mixed
    */
    public function default_api_search(Request $request)
    {
        // First, call the default search options.
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $where = [
            'active'  => true,
            'visible' => true,
        ];

        // Allow the option to bypass some of these restrictions for the searches
        if (!$request->filled('ignore_restrictions') || $request->get('ignore_restrictions') === '0') {
            if (setting('pilots.restrict_to_company')) {
                $where['airline_id'] = $user->airline_id;
            }

            if (setting('pilots.only_flights_from_current')) {
                $where['dpt_airport_id'] = $user->curr_airport_id;
            }
        }

        try {
            $this->flightRepo->resetCriteria();
            $this->flightRepo->searchCriteria($request);
            $this->flightRepo->pushCriteria(new WhereCriteria($request, $where, [
                'airline' => ['active' => true],
            ]));

            $this->flightRepo->pushCriteria(new RequestCriteria($request));

            $with = [
                'airline',
                'fares',
                'field_values',
                'simbrief' => function ($query) use ($user) {
                    return $query->with('aircraft')->where('user_id', $user->id);
                },
            ];

            $relations = [
                'subfleets',
            ];

            if ($request->has('with')) {
                $relations = explode(',', $request->input('with', ''));
            }

            foreach ($relations as $relation) {
                $with = array_merge($with, match ($relation) {
                    'subfleets' => [
                        'subfleets',
                        'subfleets.aircraft',
                        'subfleets.aircraft.bid',
                        'subfleets.fares',
                    ],
                    default => [],
                });
            }

            $flights = $this->flightRepo->with($with)->paginate();
        } catch (RepositoryException $e) {
            return response($e, 503);
        }

        foreach ($flights as $flight) {
            if (in_array('subfleets', $relations)) {
                $this->flightSvc->filterSubfleets($user, $flight);
            }

            $this->fareSvc->getReconciledFaresForFlight($flight);
        }
        // Now, let's get SimVector Schedule Cloud flights and merge them in
        $airlines = Airline::where('active', true)->pluck('icao')->toArray();

        $svdata = $this->dataService->getFlightsSmartCARS([
            'departure_icao' => $request->query('dpt_airport_id'),
            'arrival_icao'   => $request->query('arr_airport_id'),
            'operators'   => join(',', $airlines),
            'limit'          => $request->query('limit', 100),
            'format'         => 'phpvms7'
        ]);

        // We need to convert the svdata into Flight models, but not persist them in the database

    }
    public function sc_book(Request $request)
    {
        $flight_id = $request->flightId ?? $request->input('flightID');
        // if the flight id is prefixed with "SV_", we need to fetch the flight from SimVector
        if (str_starts_with($flight_id, 'SV_')) {
            $svDataService = app()->make(\Modules\SimVector\Services\DataService::class);
            //dd($svDataService);
            $flight = $svDataService->createFlight($flight_id);
            if ($flight === null) {
                return response()->json(['message' => 'Flight not found'], 404);
            }
        } else {
            // Otherwise, find the flight by ID
            $flight = Flight::find($flight_id);
            if ($flight === null) {
                return response()->json(['message' => 'Flight not found'], 404);
            }
        }
        $user = User::find($request->get('pilotID'));
        $bid = $this->bidService->addBid($flight, $user);
        // force setting the aircraft selected for smartCARS bidding
        if ($request->input('aircraftID') !== null) {
            $bid = Bid::find($bid->id);
            $bid->aircraft_id = $request->input('aircraftID');
            $bid->save();
        }
        return response()->json(["bidID" => $bid->id]);
    }
    public function sc_search(Request $request)
    {
        $output = [];

        $query = [];
        $subfleet = null;
        $limit = 100;

        if ($request->has('limit') && $request->query('limit') !== null) {
            $limit = $request->query('limit');
            $limit = min($limit, 100);
        }

        if ($request->has('departureAirport') && $request->query('departureAirport') !== null) {
            $apt = Airport::where('icao', $request->query('departureAirport'))->first();
            if (!is_null($apt)) {
                $query['dpt_airport_id'] = $apt->id;
            }
        }
        // If Current Airport Setting is enabled, force current airport as the search
        if (setting('pilots.only_flights_from_current')) {
            $query['dpt_airport_id'] = $request->user()->curr_airport_id;
        }
        if ($request->has('arrivalAirport') && $request->query('arrivalAirport') !== null) {
            $apt = Airport::where('icao', $request->query('arrivalAirport'))->first();
            if (!is_null($apt)) {
                $query['arr_airport_id'] = $apt->id;
            }
        }
        if ($request->has('aircraft') && $request->query('aircraft') !== null) {
            // Yank the subfleet by ID
            $apt = Subfleet::find($request->query('aircraft'));
            if (!is_null($apt)) {
                $subfleet = $apt->id;
            }
        }
        if (!empty($subfleet)) {
            if (empty($query)) {
                $flights = Flight::with('subfleets', 'subfleets.aircraft', 'airline')->whereHas('subfleets', function ($query) use ($subfleet) {
                    $query->where(['subfleets.id' => $subfleet, 'visible' => true]);
                })->take(100)->get();
            } else {
                $flights = Flight::where($query)->with('subfleets', 'subfleets.aircraft', 'airline')->whereHas('subfleets', function ($query) use ($subfleet) {
                    $query->where(['subfleets.id' => $subfleet, 'visible' => true]);
                })->take(100)->get();
            }
        } else {
            if (empty($query)) {
                $flights = Flight::with('subfleets', 'subfleets.aircraft', 'airline')->where('visible', true)->take($limit)->get();
            } else {
                $flights = Flight::where($query)->with('subfleets', 'subfleets.aircraft', 'airline')->where('visible', true)->take($limit)->get();
            }
        }

        foreach ($flights as $flight) {
            $aircraft = [];
            $flight = $this->flightSvc->filterSubfleets($request->user(), $flight);
            foreach ($flight->subfleets as $subfleet) {
                foreach ($subfleet->aircraft as $acf) {
                    $aircraft[] = $acf['id'];
                }
            }
            $ft_converted = floatval(number_format($flight->flight_time / 60, 2));
            $output[] = [
                "id"               => $flight->id,
                "number"           => $flight->flight_number,
                "code"             => $flight->airline->code,
                "departureAirport" => $flight->dpt_airport_id,
                "arrivalAirport"   => $flight->arr_airport_id,
                "flightLevel"      => $flight->level,
                "route"            => $flight->route ? explode(" ", $flight->route) : null,
                "distance"         => $flight->distance->local(),
                "departureTime"    => $flight->dpt_time,
                "arrivalTime"      => $flight->arr_time,
                "flightTime"       => $ft_converted,
                "daysOfWeek"       => [],
                "type"             => $this->flightType($flight->flight_type),
                "aircraft"         => sizeof($aircraft) === 1 ? $aircraft[0] : $aircraft,
                "notes"            => $flight->notes
            ];
        }
        // Now, check SimVector Schedule Cloud for flights if the module is enabled and the environment variable is set
        $airlines = Airline::where('active', true)->pluck('icao')->toArray();

        $svdata = $this->dataService->getFlightsSmartCARS([
            'departure_icao' => $request->query('departureAirport'),
            'arrival_icao'   => $request->query('arrivalAirport'),
            'operators'   => join(',', $airlines),
            'limit'          => $limit,
            'format'         => 'phpvms7'
        ]);
        $output = array_merge($output, $svdata);

        return response()->json($output);
    }
    
}
