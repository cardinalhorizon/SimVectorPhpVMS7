<?php

namespace Modules\SimVector\Http\Controllers\Api;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Enums\FlightType;
use App\Models\Flight;
use App\Services\ImportExport\FlightImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\SimVector\Services\DataService;

/**
* class ApiController
* @package Modules\SimVector\Http\Controllers\Api
*/
class ApiController extends Controller
{
    public function __construct(public DataService $dataService)
    {
    }

    public function getChecksums(): JsonResponse
    {
        // dump the tables to disk and generate checksums
        $tables = ['airports', 'aircraft', 'subfleets', 'flights'];
        $db_prefix = config('database.connections.' . config('database.default') . '.prefix', '');
        $checksums = [];
        foreach ($tables as $table) {
            try {
                $res = DB::select("CHECKSUM TABLE {$db_prefix}{$table}");
                $checksums[$table] = $res[0]->Checksum ?? $res[0]->checksum ?? null;
            } catch (\Throwable $e) {
                $checksums[$table] = null;
            }
        }

        $results = $checksums;
        $results['generated_at'] = now()->toDateTimeString();
        return response()->json($results);
    }
    public function getNewSessionToken(Request $request): JsonResponse
    {
        $response = $this->dataService->generateUserJWT($request->user());
        if (!isset($response['token'])) {
            return response()->json($response, $response['status'] ?? 500);
        }
        return response()->json([
            'token' => $response['token'],
            'grants' => $response['grants'] ?? [],
        ]);
    }
    public function getAirports(Request $request): JsonResponse
    {
        $hubs_only = $request->get('hubs_only', false);
        $airport_features = [];
        if ($hubs_only) {
            Airport::where('hub', true)->chunk(100, function ($airports) use (&$airport_features) {
                $airport_features = array_merge($airport_features ?? [], $this->generateGeoJsonFeaturesFromAirports($airports));
            });
        } else {
            Airport::chunk(3000, function ($airports) use (&$airport_features) {
                $airport_features = array_merge($airport_features ?? [], $this->generateGeoJsonFeaturesFromAirports($airports));
            });
        }
        $geojson = [
            'type'     => 'FeatureCollection',
            'features' => $airport_features,
        ];

        return response()->json($geojson);
    }
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
    private function generateGeoJsonFeaturesFromAirports($airports)
    {
        $features = [];
        foreach ($airports as $airport) {
            $features[] = [
                'type'     => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [$airport['lon'], $airport['lat']],
                ],
                'properties' => [
                    'id'   => $airport['id'],
                    'name' => $airport['name'],
                    'icao' => $airport['icao'],
                    'iata' => $airport['iata'],
                    'hub'  => $airport['hub'],
                ],
            ];
        }

        return $features;
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
