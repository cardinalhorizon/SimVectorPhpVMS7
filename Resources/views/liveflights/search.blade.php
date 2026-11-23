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
                'current_airport' => ($user && $user->curr_airport_id)
                    ? ['icao' => $user->curr_airport_id]
                    : null,
                'home_airport' => ($user && $user->home_airport_id)
                    ? ['icao' => $user->home_airport_id]
                    : null,
            ],
            'initialSaved' => $saved,
            'onlyFlightsFromCurrent' => setting('pilots.only_flights_from_current', false),
            'apiBaseUrl' => url('/api'),
            'bidsUrl' => \Illuminate\Support\Facades\Route::has('frontend.flights.bids') ? route('frontend.flights.bids') : null,
            'apiKey' => $user ? ($user->api_key ?? '') : '',
        ];
    @endphp

    <div id="app" data-app='@json($appData)'></div>
@endsection
