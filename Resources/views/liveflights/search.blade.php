@extends('simvector::layouts.frontend')

@section('scripts_head')
    <script src="{{ mix('/frontend/js/flightsearch.js', '/assets/simvector') }}" defer></script>
@endsection

@section('content')
    @php
        // Use the auth() helper to avoid direct facade calls in the array
        $user = auth()->user();
        $appData = [
            'airlines' => $airlines,
            'subfleets' => $subfleets,

            'user' => [
                'id' => $user ? $user->id : null,
                'current_airport' => ($user && $user->current_airport)
                    ? ['icao' => $user->current_airport->icao]
                    : null,
            ],
            'initialSaved' => $saved,
            'onlyFlightsFromCurrent' => setting('pilots.only_flights_from_current', false),
            'apiBaseUrl' => url('/api'),
            'apiKey' => $user ? ($user->api_key ?? '') : '',
        ];
    @endphp

    <div id="app" data-app='@json($appData)'></div>
@endsection
