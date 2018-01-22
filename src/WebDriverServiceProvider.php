<?php
namespace gpibarra\WebDriverPHP;

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
                Console\Commands\InstallWebDriverCommand::class,
                Console\Commands\StartWebDriverCommand::class,
                Console\Commands\StopWebDriverCommand::class,
                Console\Commands\UpdateWebDriverCommand::class,
                Console\Commands\StatusWebDriverCommand::class
            ]);
        }
    }
}