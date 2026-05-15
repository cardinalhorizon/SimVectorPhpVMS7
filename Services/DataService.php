<?php

namespace Modules\SimVector\Services;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataService
{
    public function __construct(
        public \App\Services\UserService $userSvc
    ) {
        // Constructor to inject dependencies
        $this->simvector_api_key = sv_setting('core.api_key', '');
        $this->simvector_api_url = env('SV_API_URL', 'https://simvector.net/api');
    }

    private string $simvector_api_url;
    private string $simvector_api_key;
    private $airlines = [];
    private $subfleets = [];

    public function generateUserJWT(User $user)
    {
        $url = $this->simvector_api_url."/v1/auth/jwt";
        $res = Http::get($url, [
            'api_key' => $this->simvector_api_key
        ]);
        $json = $res->json();
        $json['status'] = $res->status();
        return $json;
    }
    public function getAirports($icaos = null, $format = null)
    {
        // If the icaos is an array, convert it to a comma-separated string
        if (is_array($icaos)) {
            $icaos = implode(',', $icaos);
        }
        $url = $this->simvector_api_url."/v1/data/airports";
        return Http::get($url, [
            'icaos' => $icaos,
            'format' => $format,
            'api_key' => $this->simvector_api_key,
        ])->json();
    }

    public function getFlightsSmartCARS($params = [])
    {
        $res = $this->getLiveFlights(
            $params['departure_icao'] ?? '',
            $params['arrival_icao'] ?? '',
            $params['airline_icao'] ?? ''
        );
        // map the response to the output with the generateSmartCARS3Flight function
        return array_map(function ($flight) {
            return self::generateSmartCARS3Flight($flight);
        }, $res ?? []);

    }
    public function createFlight($flight_id)
    {

        // Before making any API calls, check if the flight already exists
        $existingFlight = Flight::where('id', $flight_id)->first();
        if ($existingFlight) {
            return $existingFlight; // Return the existing flight if found
        }
        // Get the flight from the API
        $api_key = $this->simvector_api_key;
        // If the flight_id starts with SV_, we need to remove it
        if (str_starts_with($flight_id, 'SV_')) {
            $flight_id = substr($flight_id, 3);
        }
        $url = $this->simvector_api_url."/v1/data/live/flight/".$flight_id;
        $res = Http::get($url, [
            'api_key' => $api_key
        ]);
        // Check if the response was successful
        if (!$res->successful()) {
            Log::error("Failed to fetch flight data from SimVector API", [
                'flight_id' => $flight_id,
                'status' => $res->status(),
                'response' => $res->body()
            ]);
            return null; // or handle error appropriately
        }
        $data = $res->json()['data'] ?? null;
        // If the airline does not exist, create the airline
        $data['airline_id'] = Airline::where('icao', $data['operator_icao'])->first()->id ?? null;
        if (!$data['airline_id']) {
            $airline = Airline::create([
                'name' => $data['operator_name'] ?? $data['operator_icao'],
                'icao' => $data['operator_icao'],
                'active' => true,
            ]);
            $data['airline_id'] = $airline->id;
        }
        return Flight::create([
            'id'               => "SV_".$data['natural_key'],
            'flight_number'    => $data['flight_number'],
            'route_code'       => "SV",
            'airline_id'       => $data['airline_id'],
            'dpt_airport_id'   => $data['departure_airport'],
            'arr_airport_id'   => $data['arrival_airport'],
            'dpt_time'         => $data['departure_time'],
            'arr_time'         => $data['arrival_time'],
            'distance'         => $data['distance'] ?? 0,
            'flight_time'      => $data['duration'] ?? 0,
            'flight_type'      => $data['type'] ?? 'J',
            'notes'            => $data['notes'] ?? '',
            'active'           => true,
            'visible'          => false,
            'owner_type'       => self::class
        ]);
    }

    public function getLiveFlights(
        string $departure_icao = null,
        string $arrival_icao = null,
    ) {
        $api_key = $this->simvector_api_key;
        $url = $this->simvector_api_url. "/v1/data/live/search";
        // build the params array based on the provided parameters
        $params = [];
        if ($departure_icao)
            $params['departure_icao'] = $departure_icao;
        if ($arrival_icao)
            $params['arrival_icao'] = $arrival_icao;
        $params['api_key'] = $api_key;
        $params['format'] = 'phpVMS7';
        $res = Http::get($url, $params)->json();
        // If there's an error in the response, log it and return an empty array
        if (isset($res['error'])) {
            Log::error("SimVector API Error: " . $res['error']['message'] ?? 'Unknown error', [
                'code' => $res['error']['code'] ?? 'N/A',
                'params' => $params
            ]);
            throw new \Exception("SimVector API Error: " . $res['error']['message'] ?? 'Unknown error');
        }
        // Provide all the subfleets for an airline based on
        $this->subfleets = Subfleet::with('aircraft')
            ->get()
            ->keyBy(function ($item) {
                // Use the first aircraft's ICAO code as the key
                if ($item->aircraft->isEmpty()) {
                    return null; // Handle case where no aircraft are associated
                }
                $firstAircraft = $item->aircraft->first();
                if (!$firstAircraft) {
                    return null; // Handle case where no aircraft are associated
                }
                // Return the ICAO code of the first aircraft
                return $firstAircraft->icao;
            });
        $this->airlines = Airline::select('id', 'icao')->get()->keyBy(function ($item) {return $item->icao; });
        return $res['data'] ?? [];
    }

    public function generateSmartCARS3Flight($flight): array {
        // Ok, now we need to grab the subfleets possible with this flight
        $aircraft = [];
        // if the flight has an aircraft_type, use that as the ICAO. If not, use the known_aircraft.
        if (isset($flight['aircraft_type']) && !empty($flight['aircraft_type'])) {
            $icaos = [$flight['aircraft_type']];
        } else {
            $icaos = $flight['known_aircraft'] ?? [];
        }

        foreach ($icaos as $icao) {
            // Get all subfleets for this ICAO
            $subfleets = $this->subfleets->filter(function ($subfleet) use ($icao) {
                return $subfleet && $subfleet->aircraft->first() && $subfleet->aircraft->first()->icao === $icao;
            });

            // If only one subfleet/aircraft exists for this ICAO, use it
            if ($subfleets->count() === 1) {
                $aircraft[] = $subfleets->first()->aircraft->first()->id;
            } else {
                // Try to find subfleet for this airline/operator
                $subfleet = $subfleets->first(function ($sf) use ($flight) {
                    return $sf->airline_id == $flight->airline->id;
                });
                if ($subfleet && $subfleet->aircraft->isNotEmpty()) {
                    // Get all the available aircraft for this subfleet
                    $aircraft[] = $subfleet->aircraft->pluck('id')->toArray();
                    $subfleet->aircraft->first();
                }
            }
        }
        // IF there's no aircraft by the end of this, we need to fallback to offering all the available aircraft
        if (empty($aircraft)) {
            // If there's no aircraft, we need to get all the available aircraft for the airline
            $aircraft = $this->filterSubfleets(Auth::user(), $this->subfleets, $this->airlines[$flight['operator_icao']]->id, $flight['departure_airport']);
        }

        return [
            "id"               => "SV_".$flight['natural_key'],
            "number"           => $flight['flight_number'],
            "code"             => "[SV] ".$flight['operator_icao'],
            "departureAirport" => $flight['departure_airport'],
            "arrivalAirport"   => $flight['arrival_airport'],
            "flightLevel"      => 0,
            "route"            => null,
            "distance"         => 0,
            "departureTime"    => $flight['departure_time'],
            "arrivalTime"      => $flight['arrival_time'],
            "flightTime"       => 0,
            "daysOfWeek"       => [],
            "type"             => "P",
            "aircraft"         => sizeof($aircraft) === 1 ? $aircraft[0] : $aircraft,
            "notes"            => $flight['notes'],
        ];
    }
    public function filterSubfleets(User $user, $subfleets, $airline_id, $dpt_airport_id)
    {
        $aircraft = [];


        // Eager load some of the relationships needed
        // $flight->load(['flight.subfleets', 'flight.subfleets.aircraft', 'flight.subfleets.fares']);

        /** @var \Illuminate\Support\Collection $subfleets */

        // If no subfleets assigned and airline subfleets are forced, get airline subfleets
        if (($subfleets === null || $subfleets->count() === 0) && setting('flights.only_company_aircraft', false)) {
            $subfleets = Subfleet::where(['airline_id' => $airline_id])->get();
        }

        // If no subfleets assigned to a flight get users allowed subfleets
        if ($subfleets === null || $subfleets->count() === 0) {
            $subfleets = $this->userSvc->getAllowableSubfleets($user);
        }

        // If subfleets are still empty return the flight
        if ($subfleets === null || $subfleets->count() === 0) {
            return $aircraft;
        }

        // Only allow aircraft that the user has access to by their rank or type rating
        if (setting('pireps.restrict_aircraft_to_rank', false) || setting('pireps.restrict_aircraft_to_typerating', false)) {
            $allowed_subfleets = $this->userSvc->getAllowableSubfleets($user)->pluck('id');
            $subfleets = $subfleets->filter(function ($subfleet, $i) use ($allowed_subfleets) {
                if ($allowed_subfleets->contains($subfleet->id)) {
                    return true;
                }

                return false;
            });
        }

        /*
         * Only allow aircraft that are at the current departure airport
         */
        $aircraft_at_dpt_airport = setting('pireps.only_aircraft_at_dpt_airport', false);
        $aircraft_not_booked = setting('bids.block_aircraft', false);

        if ($aircraft_at_dpt_airport || $aircraft_not_booked) {
            $subfleets->loadMissing('aircraft');

            foreach ($subfleets as $subfleet) {
                $subfleet->aircraft = $subfleet->aircraft->filter(
                    function ($aircraft, $i) use ($user, $dpt_airport_id, $aircraft_at_dpt_airport, $aircraft_not_booked) {
                        if ($aircraft_at_dpt_airport && $aircraft->airport_id !== $dpt_airport_id) {
                            return false;
                        }

                        if ($aircraft_not_booked && $aircraft->bid && $aircraft->bid->user_id !== $user->id) {
                            return false;
                        }

                        return true;
                    }
                )->sortBy(function (Aircraft $ac, int $_) {
                    return !empty($ac->bid);
                });
            }
        }
        // Now that we have the subfleets filtered, we can get the aircraft
        foreach ($subfleets as $subfleet) {
            foreach ($subfleet->aircraft as $acf) {
                $aircraft[] = $acf['id'];
            }
        }

        return $aircraft;
    }
}