<?php

namespace Modules\SimVector\Http\Controllers\Admin;

use App\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\SimVector\Models\SVSetting;

/**
 * Admin controller
 */
class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        // Get or create the settings record
        $settings = SVSetting::firstOrCreate(
            ['key' => 'core'],
            ['value' => [
                'api_key' => '',
                'smartcars_route_override' => false,
            ]]
        );

        return view('simvector::admin.index', [
            'settings' => $settings->value ?? [],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function create(Request $request)
    {
        return view('simvector::admin.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     */
    public function store(Request $request)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function edit(Request $request)
    {
        return view('simvector::admin.edit');
    }

    /**
     * Show the specified resource.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function show(Request $request)
    {
        return view('simvector::admin.show');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     */
    public function update(Request $request)
    {
        $smartcars_route_override = $request->input('smartcars_route_override') ? true : false;
        SVSetting::updateOrCreate([
            'key' => 'core',
        ], [
            'value' => [
                'api_key' => $request->input('api_key', ''),
                'smartcars_route_override' => $smartcars_route_override,
            ],
        ]);
        Cache::forget('sv_settings');
        flash()->success('Settings saved successfully!');
        return redirect()->route('admin.simvector.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     */
    public function destroy(Request $request)
    {
    }
}
