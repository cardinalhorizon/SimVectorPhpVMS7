@extends('simvector::layouts.admin')

@section('title', 'SimVector Settings')
@section('content')
    @include('flash::message')

    {{-- Information Warning --}}
    <div class="card border-blue-bottom">
        <div class="content">
            <div class="row">
                <div class="col-xs-12">
                    <div style="display: flex; align-items: flex-start; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
                        <div style="margin-right: 10px;">
                            <i class="fas fa-info-circle" style="color: #856404; font-size: 1.2em;"></i>
                        </div>
                        <div style="color: #856404;">
                            <strong>Note:</strong> Some settings are configured from the SimVector Platform and cannot be modified here.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('admin.simvector.update') }}">
        @csrf
        @method('PUT')

        <div class="card border-blue-bottom">
            <div class="content table-responsive">
                <div class="row">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th colspan="2">
                                    <h5>SimVector Core Settings</h5>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- API Key Setting --}}
                            <tr>
                                <td width="70%">
                                    <p><strong>SimVector Community API Key</strong></p>
                                    <p class="description">
                                        @component('admin.components.info')
                                            Enter your SimVector Community API Key. This key is used to authenticate your VA with the SimVector platform.
                                        @endcomponent
                                    </p>
                                </td>
                                <td align="center">
                                    <input
                                        type="text"
                                        name="api_key"
                                        class="form-control"
                                        value="{{ $settings['api_key'] ?? '' }}"
                                        placeholder="Enter API Key"
                                    />
                                </td>
                            </tr>

                            {{-- Schedule Cloud Override Setting --}}
                            <tr>
                                <td width="70%">
                                    <p><strong>Enable smartCARS Search Overrides</strong></p>
                                    <p class="description">
                                        @component('admin.components.info')
                                            Enable this option to allow SimVector Schedule Cloud to show flights in the default smartCARS Flight Center.
                                            This will override the smartCARS Module's search and bid management, allowing this module to handle this.
                                            Your existing phpVMS flights will show up alongide the SImVector Schedule Cloud flights.
                                        @endcomponent
                                    </p>
                                </td>
                                <td align="center">
                                    <input
                                        type="hidden"
                                        name="smartcars_route_override"
                                        value="0"
                                    />
                                    <input
                                        type="checkbox"
                                        name="smartcars_route_override"
                                        value="1"
                                        {{ isset($settings['smartcars_route_override']) && $settings['smartcars_route_override'] ? 'checked' : '' }}
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="card">
            <div class="content">
                <div class="row">
                    <div class="col-sm-12 text-right">
                        <button type="submit" class="btn btn-success">Save Settings</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
