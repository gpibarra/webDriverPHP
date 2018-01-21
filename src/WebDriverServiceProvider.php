<?php

namespace gpibarra\webDriverPHP;

use Illuminate\Support\ServiceProvider;

class WebDriverServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    /*
    public function register()
    {
    }
    */
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\installWebDriverCommand::class,
                Console\Commands\startWebDriverCommand::class,
                Console\Commands\stopWebDriverCommand::class,
                Console\Commands\updateWebDriverCommand::class
            ]);
        }
    }
}