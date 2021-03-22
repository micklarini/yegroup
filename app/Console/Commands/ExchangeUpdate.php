<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use App\Services\ExchangeRates;

class ExchangeUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exchange:update 
                            {date? : Date of rates to fetch in YYYY-MM-DD format}
                            {--D|defs : Force update currencies definitions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currencies exchange rates';

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
     * @return int
     */
    public function handle(ExchangeRates $rates)
    {
        //TODO: Add locale support
        $date = \DateTime::createFromFormat('Y-m-d', $this->argument('date') ?? date('Y-m-d'));
        if (!$date) {
            $this->error('Invalid date');
            return -1;
        }
        else {
            $date->modify('midnight');
        }
        
        try {
            $rates->fetch($date, $this->options());
        }
        catch (\Exception $e) {
            $this->error($e->getMessage());
            return -1;
        }
        $this->info('Currencies exchange rates fetch: success.');
        return 0;
    }
}
