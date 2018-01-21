<?php

namespace gpibarra\webDriverPHP\Console\Commands;

use gpibarra\WebDriverPHP\WebDriver;
use Illuminate\Console\Command;

class updateWebDriverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webDriver:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        WebDriver::updateServer();
    }
}
