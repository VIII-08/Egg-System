<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class RunForecast extends Command
{
    protected $signature = 'forecast:run';
    protected $description = 'Force run forecasting analysis (updates CSV from DB then runs Python forecasting)';

    public function handle()
    {
        $this->info('Force running forecast analysis...');
        Log::info('Forecast: Force running forecast analysis...');

        // Force set the cache flag to ensure forecast runs
        Cache::put('data_changed', true, 60);

        // --- STEP 1: UPDATE THE CSV FILE ---
        try {
            // Fixed Path (relative to project root)
            $csvPath = base_path('forecasting_scripts/data/historical_data.csv'); 
            
            // Uses 'sale_items' as discovered from your database
            $sales = DB::table('sales_transactions')
                ->join('sale_items', 'sales_transactions.id', '=', 'sale_items.sales_transaction_id')
                ->join('egg_products', 'sale_items.egg_product_id', '=', 'egg_products.id')
                ->select(
                    'egg_products.id as product_id',
                    'egg_products.name as Egg_Size',
                    'sales_transactions.created_at as Date',
                    'sale_items.quantity as Quantity_Sold'
                )
                ->orderBy('Date', 'asc')
                ->get();

            // Open file
            $file = fopen($csvPath, 'w');
            
            // Add Header Row (product_id so forecast stays correct after egg size renames)
            fputcsv($file, ['product_id', 'Egg_Size', 'Date', 'Quantity_Sold']);

            // Add Data Rows
            $rowCount = 0;
            foreach ($sales as $row) {
                $formattedDate = date('d/m/Y', strtotime($row->Date));
                fputcsv($file, [$row->product_id, $row->Egg_Size, $formattedDate, $row->Quantity_Sold]);
                $rowCount++;
            }
            
            fclose($file);
            $this->info("CSV updated successfully. Rows Exported: {$rowCount}");
            Log::info("Forecast: CSV updated successfully. Rows Exported: {$rowCount}");

        } catch (\Exception $e) {
            $this->error('CSV Export Failed: ' . $e->getMessage());
            Log::error('Forecast: CSV Export Failed. ' . $e->getMessage());
            return 1;
        }

        // --- STEP 2: RUN PYTHON ---
        $this->info('Running Python Script...');
        Log::info('Forecast: Running Python...');
        
        // Get Python path from .env or auto-detect
        $pythonPath = $this->getPythonPath();
        
        if (!$pythonPath) {
            $this->error('Python not found. Please set PYTHON_PATH in your .env file or ensure Python is in your system PATH.');
            $this->info('Common Windows paths:');
            $this->info('  - C:\\Python3x\\python.exe');
            $this->info('  - C:\\Users\\YourName\\AppData\\Local\\Programs\\Python\\Python3x\\python.exe');
            $this->info('  - Or use: python (if Python is in PATH)');
            Log::error('Forecast: Python not found.');
            return 1;
        }
        
        // Fixed path for the script location
        $scriptPath = base_path('forecasting_scripts/run_forecast.py');

        // Setup the process
        $process = new Process([$pythonPath, $scriptPath]);
        $process->setTimeout(180); // Give it 3 minutes to think
        
        $process->run(); 

        // Check results
        if ($process->isSuccessful()) {
            Cache::forget('data_changed');
            $this->info('Success: ' . $process->getOutput());
            Log::info('Forecast: FORECAST SUCCESS! Charts updated.');
            return 0;
        } else {
            $this->error('Python Error: ' . $process->getErrorOutput());
            Log::error('Forecast: Python Failed. ' . $process->getErrorOutput());
            return 1;
        }
    }

    /**
     * Get Python executable path, trying multiple methods
     */
    private function getPythonPath(): ?string
    {
        // First, check .env file
        $envPath = env('PYTHON_PATH');
        if ($envPath && file_exists($envPath)) {
            return $envPath;
        }

        // Try common Windows paths
        if (PHP_OS_FAMILY === 'Windows') {
            $commonPaths = [
                'C:\\Python39\\python.exe',
                'C:\\Python310\\python.exe',
                'C:\\Python311\\python.exe',
                'C:\\Python312\\python.exe',
                'C:\\Python313\\python.exe',
                'C:\\Program Files\\Python39\\python.exe',
                'C:\\Program Files\\Python310\\python.exe',
                'C:\\Program Files\\Python311\\python.exe',
                'C:\\Program Files\\Python312\\python.exe',
                'C:\\Program Files\\Python313\\python.exe',
            ];

            // Check AppData paths (common for user installations)
            $appData = getenv('LOCALAPPDATA');
            if ($appData) {
                $userPaths = glob($appData . '\\Programs\\Python\\Python*\\python.exe');
                $commonPaths = array_merge($commonPaths, $userPaths);
            }

            foreach ($commonPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }

            // Try 'python' command (if in PATH)
            try {
                $process = new Process(['python', '--version']);
                $process->run();
                if ($process->isSuccessful()) {
                    // Python is available, try to find the full path
                    $whereProcess = new Process(['where', 'python']);
                    $whereProcess->run();
                    if ($whereProcess->isSuccessful()) {
                        $output = trim($whereProcess->getOutput());
                        $lines = explode("\n", $output);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!empty($line) && file_exists($line)) {
                                return $line;
                            }
                        }
                    }
                    // If where fails, just return 'python' and let the system find it
                    return 'python';
                }
            } catch (\Exception $e) {
                // Ignore and continue
            }

            // Try 'python3' command (if in PATH)
            try {
                $process = new Process(['python3', '--version']);
                $process->run();
                if ($process->isSuccessful()) {
                    $whereProcess = new Process(['where', 'python3']);
                    $whereProcess->run();
                    if ($whereProcess->isSuccessful()) {
                        $output = trim($whereProcess->getOutput());
                        $lines = explode("\n", $output);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (!empty($line) && file_exists($line)) {
                                return $line;
                            }
                        }
                    }
                    return 'python3';
                }
            } catch (\Exception $e) {
                // Ignore and continue
            }
        } else {
            // Linux/Mac paths
            $commonPaths = ['/usr/bin/python3', '/usr/local/bin/python3', 'python3', 'python'];
            foreach ($commonPaths as $path) {
                $process = new Process(['which', $path]);
                $process->run();
                if ($process->isSuccessful()) {
                    $output = trim($process->getOutput());
                    if (!empty($output) && file_exists($output)) {
                        return $output;
                    }
                }
            }
        }

        return null;
    }
}

