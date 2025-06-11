<?php

namespace Modules\SimVector\Providers;

use App\Contracts\Modules\ServiceProvider;

/**
 * @package $NAMESPACE$
 */
class AppServiceProvider extends ServiceProvider
{
    private $moduleSvc;

    protected $defer = false;

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->moduleSvc = app('App\Services\ModuleService');

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        $this->registerLinks();

        // Uncomment this if you have migrations
        // $this->loadMigrationsFrom(__DIR__ . '/../$MIGRATIONS_PATH$');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        //
    }

    /**
     * Add module links here
     */
    public function registerLinks(): void
    {
        // Show this link if logged in
        // $this->moduleSvc->addFrontendLink('SimVector', '/simvector', '', $logged_in=true);

        // Admin links:
        $this->moduleSvc->addAdminLink('SimVector', '/admin/simvector');
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('simvector.php'),
        ], 'simvector');

        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'simvector');
    }

    /**
     * Register views.
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/simvector');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([$sourcePath => $viewPath,], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return str_replace('default', setting('general.theme'), $path) . '/modules/simvector';
        }, \Config::get('view.paths')), [$sourcePath]), 'simvector');
    }

    /**
     * Register translations.
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/simvector');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'simvector');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'simvector');
        }
    }
}
