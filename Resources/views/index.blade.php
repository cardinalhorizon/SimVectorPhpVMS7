@extends('simvector::layouts.frontend')

@section('title', 'SimVector')

@section('content')
    <h1>Hello World</h1>

    <p>
        This view is loaded from module: {{ config('simvector.name') }}
    </p>
@endsection
