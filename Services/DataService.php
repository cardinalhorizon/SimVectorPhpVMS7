<?php

namespace Modules\SimVector\Services;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Modules\SimVector\Exceptions\SimVectorApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataService
{
    public function __construct(
        public \App\Services\UserService $userSvc
    ) {
        $this->simvector_api_key = (string) sv_setting('core.api_key', '');
        $this->simvector_api_url = self::normalizeBaseUrl(env('SV_API_URL', 'https://api.simvector.net/v1'));
    }

    private string $simvector_api_url;
    private string $simvector_api_key;
    private $airlines = [];
    private $subfleets = [];

    /**
     * Accepts legacy values (".../api") as well as the current ".../v1" form.
     */
    public static function normalizeBaseUrl(string $url): string
    {
        $url = rtrim($url, '/');
        if (!preg_match('#/v\d+$#', $url)) {
            $url .= '/v1';
        }

        return $url;
    }

    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders(['x-api-key' => $this->simvector_api_key])
            ->acceptJson()
            ->timeout(20);
    }

    /**
     * Throw a SimVectorApiException for any non-success (and non-304) response.
     */
    private function ensureOk(\Illuminate\Http\Client\Response $res): \Illuminate\Http\Client\Response
    {
        if ($res->successful() || $res->status() === 304) {
            return $res;
        }
        $json = $res->json();
        $err = is_array($json) ? ($json['error'] ?? $json) : [];
        $code = is_array($err) ? ($err['code'] ?? null) : (is_string($err) ? $err : null);
        $message = is_array($err) ? ($err['message'] ?? null) : null;
        Log::error('SimVector API error', ['status' => $res->status(), 'code' => $code, 'body' => $res->body()]);

        throw new SimVectorApiException($res->status(), $code ?: 'upstream_error', $message ?: 'SimVector API request failed');
    }

    public function generateUserJWT(User $user)
    {
        $res = $this->http()->get($this->simvector_api_url.'/auth/jwt');
        $json = $res->json() ?? [];
        $json['status'] = $res->status();

        return $json;
    }

    public function getAirports($icaos = null, $format = null)
    {
        if (is_array($icaos)) {
            $icaos = implode(',', $icaos);
        }

        return $this->http()->get($this->simvector_api_url.'/data/airports', [
            'icaos'  => $icaos,
            'format' => $format,
        ])->json();
    }

    /**
     * Operators selected by the community. Cached briefly.
     */
    public function getSelectedOperators(): array
    {
        return Cache::remember('simvector.operators.'.md5($this->simvector_api_key), 300, function () {
            $res = $this->ensureOk($this->http()->get($this->simvector_api_url.'/data/selected_operators'));

            return $res->json('data') ?? $res->json() ?? [];
        });
    }

    /**
     * Static airports + routes index. Supports conditional requests: pass the
     * client's If-None-Match value; returns ['status'=>304|200,'etag'=>..,'body'=>..].
     */
    public function getRoutes(array $filters = [], ?string $ifNoneMatch = null): array
    {
        $headers = $ifNoneMatch ? ['If-None-Match' => $ifNoneMatch] : [];
        $params = array_filter(
            \Illuminate\Support\Arr::only($filters, ['operators', 'departure_icao', 'arrival_icao']),
            fn ($v) => $v !== null && $v !== ''
        );
        $res = $this->ensureOk($this->http()->withHeaders($headers)->get($this->simvector_api_url.'/data/routes', $params));

        return [
            'status' => $res->status(),
            'etag'   => $res->header('ETag'),
            'body'   => $res->status() === 304 ? null : $res->json(),
        ];
    }

    /**
     * Raw Schedule Cloud search. Returns the full envelope {data, meta, now}.
     * depth: shallow|deep, limit: 1-500, cursor from meta.next_cursor.
     */
    public function searchLive(array $p): array
    {
        $params = array_filter([
            'departure_icao' => $p['departure_icao'] ?? null,
            'arrival_icao'   => $p['arrival_icao'] ?? null,
            'operators'      => is_array($p['operators'] ?? null) ? implode(',', $p['operators']) : ($p['operators'] ?? null),
            'format'         => $p['format'] ?? 'schedule_cloud',
            'depth'          => $p['depth'] ?? null,
            'limit'          => $p['limit'] ?? null,
            'cursor'         => $p['cursor'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->ensureOk($this->http()->get($this->simvector_api_url.'/data/live/search', $params))->json() ?? [];
    }

    public function getFlightsSmartCARS($params = [])
    {
        $res = $this->getLiveFlights(
            $params['departure_icao'] ?? null,
            $params['arrival_icao'] ?? null,
            $params['operators'] ?? null,
            isset($params['limit']) ? (int) $params['limit'] : null,
        );

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
        // If the flight_id starts with SV_, we need to remove it
        if (str_starts_with($flight_id, 'SV_')) {
            $flight_id = substr($flight_id, 3);
        }
        $res = $this->http()->get($this->simvector_api_url.'/data/live/flight/'.$flight_id);
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
        ?string $departure_icao = null,
        ?string $arrival_icao = null,
        ?string $operators = null,
        ?int $limit = null,
    ) {
        $res = $this->searchLive([
            'departure_icao' => $departure_icao,
            'arrival_icao'   => $arrival_icao,
            'operators'      => $operators,
            'limit'          => $limit,
            'format'         => 'phpVMS7',
        ]);
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