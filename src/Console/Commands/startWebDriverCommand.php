<?php
namespace gpibarra\WebDriverPHP\Console\Commands;

use gpibarra\WebDriverPHP\WebDriver;
use Illuminate\Console\Command;

class StartWebDriverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webDriver:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start WebDriver if is stopped';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        WebDriver::startServer();
    }
}
