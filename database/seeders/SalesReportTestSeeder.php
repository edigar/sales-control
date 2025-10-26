<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\Seller;
use Illuminate\Database\Seeder;

class SalesReportTestSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Creating test sellers...');

        $sellers = [
            [
                'name' => 'John Doe',
                'email' => 'john_doe@example.com',
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane_doe@example.com',
            ],
            [
                'name' => 'John Smith',
                'email' => 'john_smith@example.com',
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane_smith@example.com',
            ],
        ];

        foreach ($sellers as $sellerData) {
            $seller = Seller::create($sellerData);
            $this->command->info("Seller created: {$seller->name} ({$seller->email})");
        }

        $this->command->info('');
        $this->command->info('Creating test sales for today...');

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $sales = [
            ['seller_id' => 1, 'amount' => 250.00, 'commission' => 21.25, 'date' => $today],
            ['seller_id' => 1, 'amount' => 150.00, 'commission' => 12.75, 'date' => $today],
            ['seller_id' => 1, 'amount' => 350.00, 'commission' => 29.75, 'date' => $today],

            ['seller_id' => 2, 'amount' => 500.00, 'commission' => 42.50, 'date' => $today],
            ['seller_id' => 2, 'amount' => 300.00, 'commission' => 25.50, 'date' => $today],

            ['seller_id' => 3, 'amount' => 1000.00, 'commission' => 85.00, 'date' => $today],
            ['seller_id' => 3, 'amount' => 750.00, 'commission' => 63.75, 'date' => $today],
            ['seller_id' => 3, 'amount' => 450.00, 'commission' => 38.25, 'date' => $today],
            ['seller_id' => 3, 'amount' => 200.00, 'commission' => 17.00, 'date' => $today],

            ['seller_id' => 1, 'amount' => 100.00, 'commission' => 8.50, 'date' => $yesterday],
            ['seller_id' => 2, 'amount' => 200.00, 'commission' => 17.00, 'date' => $yesterday],
        ];

        foreach ($sales as $saleData) {
            Sale::create($saleData);
        }

        $this->command->info(count($sales) . ' sales created');
        $this->command->info('');
        $this->command->newLine();

        $this->command->info('Expected report summary for TODAY:');
        $this->command->info('─────────────────────────────────────────────────');

        $sellersWithSales = Seller::whereIn('id', [1, 2, 3])->get();

        foreach ($sellersWithSales as $seller) {
            $todaySales = Sale::where('seller_id', $seller->id)
                ->where('date', $today)
                ->get();

            if ($todaySales->isNotEmpty()) {
                $totalSales = $todaySales->count();
                $totalAmount = $todaySales->sum('amount');
                $totalCommission = $todaySales->sum('commission');

                $this->command->info("{$seller->name} ({$seller->email})");
                $this->command->info("   Sales: {$totalSales}");
                $this->command->info("   Total: $ " . number_format($totalAmount, 2, ',', '.'));
                $this->command->info("   Commission: $ " . number_format($totalCommission, 2, ',', '.'));
                $this->command->info('');
            }
        }

        $this->command->info('─────────────────────────────────────────────────');
        $this->command->info('Jane Smith does not have sales today and will not receive email');
        $this->command->newLine();

        $this->command->info('To send the reports, execute:');
        $this->command->warn('   php artisan reports:send-daily-sales');
        $this->command->newLine();

        $this->command->info('View emails at:');
        $this->command->warn('   http://localhost:8025');
    }
}

