<?php

namespace Modules\SimVector\Providers;

use App\Contracts\Modules\ServiceProvider;
use App\Services\ModuleService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\CHJumpSeat\Models\CHSetting;
use Modules\SimVector\Models\SVSetting;

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
        $this->moduleSvc = app(ModuleService::class);

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();

        $this->registerLinks();
        $this->copyAssets();
        // Uncomment this if you have migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton('sv_settings', function () {
            return Cache::remember('sv_settings', 60, function () {
                // Check if the table exists before trying to query it (in case of fresh install without running migrations)
                if (!\Schema::hasTable('sv_settings')) {
                    return [];
                }
                return SVSetting::getModuleSettings(self::class);
            });
        });
    }

    /**
     * Add module links here
     */
    public function registerLinks(): void
    {
        // Show this link if logged in
        $this->moduleSvc->addFrontendLink('SimVector Flights', '/simvector/flights', 'bi bi-send-arrow-up', $logged_in=true);

        // Admin links:
        $this->moduleSvc->addAdminLink('SimVector', '/admin/simvector', 'pe-7s-global');
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

    public function copyAssets()
    {
        $name = 'SimVector';
        $moduleName = strtolower($name);
        $sourcePath = module_path($name, 'public');

        // Define the public target path
        $targetPath = public_path('assets/' . $moduleName);
        if (File::exists($sourcePath)) {
            // Check if target exists and if source is newer
            // This simple check can be improved (e.g., by comparing a version file or manifest)
            // For simplicity, we'll copy if target doesn't exist or if source directory has newer files (basic check)
            $needsPublishing = !File::exists($targetPath);

            if (!$needsPublishing) {
                // A more robust check: compare modification times of key files or a manifest
                // This is a simplified check, consider a manifest file for production
                $sourceManifest = $sourcePath . '/mix-manifest.json'; // If Vite generates one
                $targetManifest = $targetPath . '/mix-manifest.json';
                if (File::exists($sourceManifest) && (!File::exists($targetManifest) || File::lastModified($sourceManifest) > File::lastModified($targetManifest))) {
                    $needsPublishing = true;
                } elseif (!File::exists($sourceManifest) && File::lastModified($sourcePath) > File::lastModified($targetPath)) {
                    // Fallback if no manifest, less reliable for nested structures
                    $needsPublishing = true;
                }
            }

            if ($needsPublishing) {
                if (!File::isDirectory(dirname($targetPath))) {
                    File::makeDirectory(dirname($targetPath), 0755, true, true);
                }
                // Clear the old directory before copying to ensure deleted files are removed
                if (File::exists($targetPath)) {
                    File::deleteDirectory($targetPath);
                }
                File::copyDirectory($sourcePath, $targetPath);
            }
        }

    }
}
