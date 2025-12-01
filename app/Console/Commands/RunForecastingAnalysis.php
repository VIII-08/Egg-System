<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;


class RunForecastingAnalysis extends Command
{
    protected $signature = 'forecast:run';
    protected $description = 'Runs the external Python script to update the sales forecast plots and numbers.';

    public function handle()
    {
        $this->info('Starting Prophet forecasting analysis...');

        $this->exportLatestSalesToCsv();

        // --- DEFINE YOUR PATHS HERE ---

        // 1. Path to your Python executable
        //    Windows: Find python.exe -> Right-click -> Properties -> Copy "Location" and add \python.exe
        //    Example for Windows: 'C:\Users\VIII\AppData\Local\Programs\Python\Python310\python.exe'
        //    Mac/Linux: Usually just 'python' or 'python3' is enough.
        $pythonExecutablePath = 'C:\Users\VIII\AppData\Local\Programs\Python\Python310\python.exe'; // <-- **UPDATE THIS PATH IF NEEDED**

        // 2. Path to your Python script within the Laravel project
        $scriptPath = base_path('forecasting_scripts/run_forecast.py');
        
        // --- END OF PATHS ---
        
        if (!file_exists($scriptPath)) {
            $this->error("Python script not found at: {$scriptPath}");
            return Command::FAILURE;
        }

        // We give the process a longer timeout (e.g., 5 minutes) as forecasting can be slow
        $process = new Process([$pythonExecutablePath, $scriptPath], null, null, null, 300);

        try {
            $process->mustRun();
            
            $this->info('Forecasting analysis completed successfully.');
            $this->line('Script Output:');
            $this->comment($process->getOutput());
            
            return Command::SUCCESS;

        } catch (ProcessFailedException $exception) {
            $this->error('The forecasting script failed to execute.');
            $this->error($exception->getMessage());
            return Command::FAILURE;
        }
    }

    private function exportLatestSalesToCsv(): void
    {
        $csvPath = base_path('forecasting_scripts/data/historical_data.csv');
        $directory = dirname($csvPath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $handle = fopen($csvPath, 'w');
        if ($handle === false) {
            $this->error("Unable to open CSV for writing at {$csvPath}");
            return;
        }

        fputcsv($handle, ['Date', 'Egg_Size', 'Price', 'Quantity_Produced', 'Quantity_Sold', 'Revenue']);

        SaleItem::query()
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->join('egg_products', 'sale_items.egg_product_id', '=', 'egg_products.id')
            ->select(
                'sales_transactions.created_at',
                'egg_products.name as egg_size',
                'sale_items.price',
                'sale_items.quantity'
            )
            ->orderBy('sales_transactions.created_at')
            ->chunk(1000, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    $date = Carbon::parse($row->created_at)->format('d/m/Y');
                    $eggSize = $this->normalizeEggSizeForProphet(strtoupper(trim($row->egg_size)));
                    $price = (float) $row->price;
                    $quantity = (int) $row->quantity;
                    $revenue = $price * $quantity;

                    fputcsv($handle, [
                        $date,
                        $eggSize,
                        $price,
                        0,
                        $quantity,
                        $revenue,
                    ]);
                }
            });

        fclose($handle);

        $this->info('Exported latest sales data to forecasting CSV.');
    }

    /**
     * Normalize egg size names to match Prophet forecast expectations.
     * Maps database names to Prophet forecast keys.
     */
    private function normalizeEggSizeForProphet(string $eggSize): string
    {
        $mapping = [
            'XL' => 'X-LARGE',
            'X-LARGE' => 'X-LARGE',
            'X LARGE' => 'X-LARGE',
            'PULLETS' => 'PULLETS',
            'PULLET' => 'PULLETS',
            'SMALL' => 'SMALL',
            'MEDIUM' => 'MEDIUM',
            'LARGE' => 'LARGE',
            'JUMBO' => 'JUMBO',
            'PEWEE' => 'PEWEE',
            'BROKEN EGGS' => 'BROKEN EGGS',
        ];

        return $mapping[$eggSize] ?? $eggSize;
    }
}
