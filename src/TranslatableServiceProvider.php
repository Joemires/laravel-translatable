<?php

namespace Joemires\Translatable;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Console\AboutCommand;

class TranslatableServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/translation.php' => config_path('translation.php'),
        ]);

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        AboutCommand::add('Joemires - Laravel Translatable', fn () => ['Version' => '1.0.0']);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/translation.php', 'translation'
        );
    }
}
