<?php

namespace Modules\SimVector\Http\Controllers\Frontend;

use App\Contracts\Controller;
use App\Models\Bid;
use App\Repositories\AirlineRepository;
use App\Repositories\SubfleetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class $CLASS$
 * @package 
 */
class FlightsController extends Controller
{
    public function __construct(
        public AirlineRepository $airlineRepo,
        public SubfleetRepository $subfleetRepo,

    ){}
    /**
     * This is an override of the default flights index page to show the integrated SimVector flight search
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function search(Request $request)
    {
        // Determine if we're overriding the default flights search page
        // If we are, we must provide a custom view with both of them in the page
        // If not, we can just return the default view
        $override_default_flights_page = sv_setting('core.override_flights_search', false);

        if ($override_default_flights_page) {
            $view = 'simvector::flights.search_override';
        } else {
            $view = 'simvector::liveflights.search';
        }

        $saved_flights = [];
        $bids = Bid::where('user_id', Auth::id())->get();
        foreach ($bids as $bid) {
            if (!$bid->flight) {
                $bid->delete();
                continue;
            }
            $saved_flights[$bid->flight_id] = $bid->id;
        }

        return view($view, [
            'airlines'      => $this->airlineRepo->selectBoxList(true),
            'saved' => $saved_flights,
            'airports'      => [],
            'subfleets'     => $this->subfleetRepo->selectBoxList(true),
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
        return view('simvector::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
    {
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
        return view('simvector::show');
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
        return view('simvector::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     */
    public function update(Request $request)
    {
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
