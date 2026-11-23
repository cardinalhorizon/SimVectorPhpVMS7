<?php

namespace Modules\SimVector\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Services\BidService;
use App\Services\ImportExport\FlightImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\SimVector\Services\DataService;

/**
* class ApiController
* @package Modules\SimVector\Http\Controllers\Api
*/
class FlightsController extends Controller
{
    public function __construct(
        public DataService $dataService,
        public BidService $bidService,
    ){}
    public function search(Request $request)
    {
        // Legacy params (departure_airport/arrival_airport) are still accepted.
        $res = $this->dataService->searchLive([
            'departure_icao' => $request->input('departure_icao', $request->input('departure_airport')),
            'arrival_icao'   => $request->input('arrival_icao', $request->input('arrival_airport')),
            'operators'      => $request->input('operators'),
            'depth'          => $request->input('depth'),
            'limit'          => $request->input('limit'),
            'cursor'         => $request->input('cursor'),
            'format'         => 'schedule_cloud',
        ]);

        // Envelope: {data, meta, now}
        return response()->json($res);
    }

    public function operators()
    {
        return response()->json(['data' => $this->dataService->getSelectedOperators()]);
    }

    public function routes(Request $request)
    {
        $res = $this->dataService->getRoutes(
            $request->only(['operators', 'departure_icao', 'arrival_icao']),
            $request->header('If-None-Match')
        );
        if ($res['status'] === 304) {
            return response('', 304)->header('ETag', $res['etag'] ?? '');
        }

        $out = response()->json($res['body']);
        if ($res['etag']) {
            $out->header('ETag', $res['etag']);
        }

        return $out;
    }

    public function book(Request $request)
    {
        // Check if there's an existing flight with that ID locally. If so, don't create a new one
        $flightData = $this->dataService->createFlight($request->flight_id);
        if (!$flightData) {
            return response()->json(['error' => 'Flight not found'], 404);
        }
        $this->bidService->addBid($flightData, auth()->user());

        return response()->json(['message' => 'Flight booked successfully', 'flight_id' => $flightData->id]);
    }
    public function unbook(Request $request)
    {
        $flight = Flight::find($request->flight_id);
        if (!$flight) {
            return response()->json(['error' => 'Flight not found'], 404);
        }
        $this->bidService->removeBid($flight, Auth::user());

        return response()->json(['message' => 'Bid removed']);
    }
}
