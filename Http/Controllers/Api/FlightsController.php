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
        $flights = $this->dataService->getLiveFlights($request->departure_airport, $request->arrival_airport);
        return response()->json($flights);
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
        $this->bidService->removeBid($request->flight_id, Auth::user());
    }
}
