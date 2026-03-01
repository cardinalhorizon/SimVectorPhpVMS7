<?php

namespace Modules\SimVector\Providers;

use Illuminate\Routing\Router;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\SimVector\Http\Controllers\Api\ScheduleCloudController;

/**
 * Register the routes required for your module here
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The root namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $namespace = 'Modules\SimVector\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @param  Router $router
     * @return void
     */
    public function before(Router $router)
    {
        //
    }

    /**
     * Define the routes for the application.
     *
     * @param \Illuminate\Routing\Router $router
     *
     * @return void
     */
    public function map(Router $router)
    {
        $this->registerWebRoutes();
        $this->registerAdminRoutes();
        $this->registerApiRoutes();
    }

    /**
     *
     */
    protected function registerWebRoutes(): void
    {
        $config = [
            'as'         => 'simvector.',
            'prefix'     => 'simvector',
            'namespace'  => $this->namespace.'\Frontend',
            'middleware' => ['web'],
        ];

        Route::group($config, function() {
            $this->loadRoutesFrom(__DIR__.'/../Http/Routes/web.php');
        });
    }

    protected function registerAdminRoutes(): void
    {
        $config = [
            'as'         => 'admin.simvector.',
            'prefix'     => 'admin/simvector',
            'namespace'  => $this->namespace.'\Admin',
            'middleware' => ['web', 'role:admin'],
        ];

        Route::group($config, function() {
            $this->loadRoutesFrom(__DIR__.'/../Http/Routes/admin.php');
        });
    }

    /**
     * Register any API routes your module has. Remove this if you aren't using any
     */
    protected function registerApiRoutes(): void
    {
        $config = [
            'as'         => 'api.simvector.',
            'prefix'     => 'api/simvector',
            'namespace'  => $this->namespace.'\Api',
            'middleware' => ['api'],
        ];

        Route::group($config, function() {
            $this->loadRoutesFrom(__DIR__.'/../Http/Routes/api.php');
        });

        // Override the default API routes as required
        Route::group([
            'middleware' => ['api', 'api.auth'],
        ], function() {
            //Route::get('/api/flights/search', [ScheduleCloudController::class, 'default_api_search'])->name('api.flights.search');
        });
        // Override the smartCARS API Routes
        if (sv_setting('core.smartcars_route_override', false)) {
            $sc_headers = '\Modules\SmartCARS3phpVMS7Api\Http\Middleware\SCHeaders';
            $sc_auth = '\Modules\SmartCARS3phpVMS7Api\Http\Middleware\SCAuth';
            Route::group([
                'prefix' => '/api/smartcars/flights',
                'controller' => ScheduleCloudController::class,
                'middleware' => [$sc_headers, $sc_auth]
            ], function () {
                Route::match(['post', 'options'], '/book', 'sc_book');
                Route::match(['get', 'options'], '/search', 'sc_search');
            });
        }
    }
}
