<?php

namespace App\Console\Commands;

use App\Jobs\SendDailySalesReportsToAdmin;
use App\Jobs\SendDailySalesReportsToSellers;
use Illuminate\Console\Command;

class SendDailySalesReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-daily-sales {date?} {--sync : Execute synchronously without queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily sales reports to admin and sellers';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->argument('date');
        $sync = $this->option('sync');

        $this->info('Sending daily sales reports...');
        $this->newLine();

        $this->info('Sending reports to administrators...');
        if ($sync) {
            SendDailySalesReportsToAdmin::dispatchSync($date);
        } else {
            SendDailySalesReportsToAdmin::dispatch($date);
        }

        $this->info('Sending reports to sellers...');
        if ($sync) {
            SendDailySalesReportsToSellers::dispatchSync($date);
        } else {
            SendDailySalesReportsToSellers::dispatch($date);
        }

        $this->newLine();
        
        if ($sync) {
            $this->success('Reports sent successfully!');
        } else {
            $this->success('Jobs added to queue successfully!');
            $this->warn('Make sure the queue worker is running: php artisan queue:work');
        }

        if ($date) {
            $this->info("Report date: {$date}");
        } else {
            $this->info('Report date: today (' . now()->toDateString() . ')');
        }

        return Command::SUCCESS;
    }
}

