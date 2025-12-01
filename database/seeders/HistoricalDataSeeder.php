<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\EggProduct;
use App\Models\ProductionLog;
use App\Models\SalesTransaction;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HistoricalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Starting historical data import...");

        $csvPath = database_path('seeders/data/historical_data.csv');
        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        // --- Use a specific user, or create one if none exist ---
        $user = User::where('role', 'staff-marketing')->first();
        if (!$user) {
            $user = User::factory()->create(['name' => 'Seeded Staff', 'role' => 'staff-marketing']);
        }

        // --- Create a case-insensitive map of products and aliases ---
        $productMap = EggProduct::all()
            ->mapWithKeys(fn ($product) => [strtolower($product->name) => $product->id]);

        $productAliases = [
            'pewee'    => 'pullets',
            'pullets'  => 'pullets',
            'small'    => 'small',
            'medium'   => 'medium',
            'large'    => 'large',
            'x-large'  => 'xl',
            'xl'       => 'xl',
            'jumbo'    => 'jumbo',
        ];

        // --- Read the entire CSV file into a collection ---
        $data = new Collection();
        if (($handle = fopen($csvPath, 'r')) !== FALSE) {
            fgetcsv($handle); // Skip header row
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data->push([
                    'date'              => Carbon::createFromFormat('d/m/Y', trim($row[0])),
                    'egg_size'          => trim($row[1]),
                    'price'             => (float)$row[2],
                    'quantity_produced' => (int)$row[3],
                    'quantity_sold'     => (int)$row[4],
                    'revenue'           => (float)$row[5],
                ]);
            }
            fclose($handle);
        }

        // --- Group all data by date ---
        $groupedByDate = $data->groupBy(fn ($row) => $row['date']->toDateString());

        // --- Rebase historical dates so the most recent record becomes today ---
        $sortedDateKeys = $groupedByDate->keys()->sort()->values();
        $rebasedDates = $sortedDateKeys->mapWithKeys(function ($originalDate, $index) use ($sortedDateKeys) {
            $daysFromEnd = ($sortedDateKeys->count() - 1) - $index;
            $newDate = Carbon::now()->subDays($daysFromEnd)->startOfDay();
            return [$originalDate => $newDate];
        });

        // --- Process each day within a single database transaction for safety ---
        DB::transaction(function () use ($groupedByDate, $rebasedDates, $user, $productMap, $productAliases) {

            foreach ($groupedByDate as $dateString => $dailyRecords) {
                $targetDate = $rebasedDates[$dateString];
                
                // --- 1. Filter out only the records that represent a sale for this day ---
                $salesDataForDay = $dailyRecords->where('quantity_sold', '>', 0);
                
                // Only create a parent transaction if there were actual sales on this day
                if ($salesDataForDay->isNotEmpty()) {

                    // --- 2. Calculate the total amount for THIS transaction ---
                    $totalAmount = $salesDataForDay->sum('revenue');
                    
                    // --- 3. Create the PARENT SalesTransaction record ---
                    $transaction = SalesTransaction::create([
                        'user_id'       => $user->id,
                        'total_amount'  => $totalAmount,
                        'customer_name' => 'Historical Sale',
                        'created_at'    => $targetDate,
                        'updated_at'    => $targetDate,
                    ]);

                    // --- 4. Loop through the sales data again and create the CHILD SaleItem records ---
                    foreach ($salesDataForDay as $saleItemData) {
                        $productName = strtolower($saleItemData['egg_size']);
                        $canonicalName = $productAliases[$productName] ?? $productName;

                        if (isset($productMap[$canonicalName])) {
                            SaleItem::create([
                                'sales_transaction_id' => $transaction->id,
                                'egg_product_id'       => $productMap[$canonicalName],
                                'quantity'             => $saleItemData['quantity_sold'],
                                'price'                => $saleItemData['price'],
                                'created_at'           => $targetDate,
                                'updated_at'           => $targetDate,
                            ]);
                        }
                    }
                }
            }
        });
        
        $this->command->info("Seeder finished processing.");
    }
}