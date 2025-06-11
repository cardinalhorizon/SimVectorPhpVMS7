<?php

namespace Modules\SimVector\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Services\ImportExport\FlightImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
* class ApiController
* @package Modules\SimVector\Http\Controllers\Api
*/
class ApiController extends Controller
{
    /**
    * Just send out a message
    *
    * @param Request $request
    *
    * @return mixed
    */
    public function ingestData(Request $request)
    {
        // Determine what data we're trying to ingest
        $data = $request->all();
        Log::info('Ingesting data', ['data' => $data]);
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'flights':
                    // Ingest the flights
                    $this->IngestFlights($value);
                    break;
                default:
                    // Handle other data types if needed
                    break;
            }
        }
        return response()->json([
            'status' => 'success',
            'message' => 'Data ingested successfully',
        ]);
    }
    private function IngestFlights($data)
    {
        // if the data is not an array, json decode it
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        $importer = new FlightImporter();
        foreach ($data as $index=>$item) {
            $importer->import($item, $index);
        }

    }
    /**
    * Handles /hello
    *
    * @param Request $request
    *
    * @return mixed
    */
    public function hello(Request $request)
    {
        // Another way to return JSON, this for a custom response
        // It's recommended to use Resources for responses from the database
        return response()->json([
            'name' => Auth::user()->name,
        ]);
    }
    
}
