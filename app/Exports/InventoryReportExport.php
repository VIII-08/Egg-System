<?php

namespace App\Exports;

use App\Models\ProductionLog;
use App\Models\ChickenStockLog;
use App\Models\FarmStat;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InventoryReportExport implements FromCollection, WithHeadings, WithCustomStartCell, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return string
     */
    public function startCell(): string
    {
        return 'A4'; // Start from row 4 to allow for header
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        // Get all production logs in the date range
        $productionLogs = ProductionLog::with('eggProduct')
            ->whereBetween('log_date', [$startDate, $endDate])
            ->get();
        
        // Group by date and product name
        $logsByDate = [];
        foreach ($productionLogs as $log) {
            // Normalize date to Y-m-d format
            $dateKey = Carbon::parse($log->log_date)->format('Y-m-d');
            $productName = strtoupper($log->eggProduct->name ?? '');
            
            if (!isset($logsByDate[$dateKey])) {
                $logsByDate[$dateKey] = [];
            }
            if (!isset($logsByDate[$dateKey][$productName])) {
                $logsByDate[$dateKey][$productName] = 0;
            }
            $logsByDate[$dateKey][$productName] += $log->quantity;
        }
        
        // Map product names to column names
        $eggSizeMap = [
            'PULLETS' => 'PULLETS',
            'SMALL' => 'SMALL',
            'MEDIUM' => 'MEDIUM',
            'LARGE' => 'LARGE',
            'X-LARGE' => 'X-LARGE',
            'XL' => 'X-LARGE',
            'JUMBO' => 'JUMBO',
            'DAMAGED' => 'DAMAGED',
            'BROKEN EGGS' => 'DAMAGED',
        ];
        
        // Get current chicken stock as baseline
        $currentStock = FarmStat::where('stat_key', 'current_chicken_stock')->value('stat_value') ?? 0;
        
        // Get all chicken stock adjustments up to the end date
        $stockAdjustments = ChickenStockLog::whereDate('created_at', '<=', $endDate->endOfDay())
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Generate all dates in the range
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        // Calculate stock count for each day
        $stockByDate = [];
        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            
            // Calculate stock for this date by working backwards from current stock
            $stockCount = $currentStock;
            
            foreach ($stockAdjustments as $adjustment) {
                $adjustmentDate = Carbon::parse($adjustment->created_at)->format('Y-m-d');
                
                // If adjustment is after this date, reverse it
                if ($adjustmentDate > $dateKey) {
                    if ($adjustment->adjustment_type === 'addition') {
                        $stockCount -= $adjustment->quantity;
                    } else {
                        $stockCount += $adjustment->quantity;
                    }
                }
            }
            
            // Ensure stock count is not negative
            $stockCount = max(0, $stockCount);
            $stockByDate[$dateKey] = $stockCount;
        }
        
        // Build daily report
        $dailyReport = collect([]);
        $totals = [
            'PULLETS' => 0,
            'SMALL' => 0,
            'MEDIUM' => 0,
            'LARGE' => 0,
            'X-LARGE' => 0,
            'JUMBO' => 0,
            'DAMAGED' => 0,
        ];
        
        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $dayNumber = $date->day;
            
            $row = [
                'day' => $dayNumber,
                'hens' => $stockByDate[$dateKey] ?? $currentStock, // Use calculated stock count
                'PULLETS' => 0,
                'SMALL' => 0,
                'MEDIUM' => 0,
                'LARGE' => 0,
                'X-LARGE' => 0,
                'JUMBO' => 0,
                'DAMAGED' => 0,
            ];
            
            // Get production for this date
            if (isset($logsByDate[$dateKey])) {
                foreach ($logsByDate[$dateKey] as $productName => $quantity) {
                    // Map product name to column
                    $columnName = $eggSizeMap[$productName] ?? null;
                    
                    if ($columnName && isset($row[$columnName])) {
                        $row[$columnName] = $quantity;
                        $totals[$columnName] += $quantity;
                    }
                }
            }
            
            $dailyReport->push($row);
        }
        
        // Add totals row
        $dailyReport->push([
            'day' => 'TOTAL',
            'hens' => '', // Empty for totals row
            'PULLETS' => $totals['PULLETS'],
            'SMALL' => $totals['SMALL'],
            'MEDIUM' => $totals['MEDIUM'],
            'LARGE' => $totals['LARGE'],
            'X-LARGE' => $totals['X-LARGE'],
            'JUMBO' => $totals['JUMBO'],
            'DAMAGED' => $totals['DAMAGED'],
        ]);

        return $dailyReport;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'DAYS',
            'HENS',
            'PULLETS',
            'SMALL',
            'MEDIUM',
            'LARGE',
            'X-LARGE',
            'JUMBO',
            'DAMAGED',
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $monthYear = Carbon::parse($this->startDate)->format('F, Y');
        
        // Set header row 1
        $sheet->setCellValue('A1', strtoupper($monthYear));
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set header row 2
        $sheet->setCellValue('A2', 'EGG PRODUCTION (Grams)');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set row 3 headers (HENS, 40-49, etc.)
        $sheet->setCellValue('B3', 'HENS');
        $sheet->setCellValue('C3', '40-49');
        $sheet->setCellValue('D3', '50-54');
        $sheet->setCellValue('E3', '55-59');
        $sheet->setCellValue('F3', '60-64');
        $sheet->setCellValue('G3', '65-69');
        $sheet->setCellValue('H3', '70 up');
        $sheet->getStyle('B3:I3')->getFont()->setBold(true);
        $sheet->getStyle('B3:I3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the column headers (row 4)
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle('A4:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the TOTAL row
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A' . $lastRow . ':I' . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':I' . $lastRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD3D3D3');
        
        return [];
    }
}

