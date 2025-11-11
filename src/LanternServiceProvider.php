<?php

namespace Lantern;

use Illuminate\Support\ServiceProvider;
use Lantern\Commands\MakeActionCommand;
use Lantern\Commands\MakeFeatureCommand;

class LanternServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/lantern.php', 'lantern'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Register commands
            $this->commands([
                MakeActionCommand::class,
                MakeFeatureCommand::class,
            ]);

            // Publish config
            $this->publishes([
                __DIR__.'/../config/lantern.php' => config_path('lantern.php'),
            ], 'lantern-config');
        }
    }
}