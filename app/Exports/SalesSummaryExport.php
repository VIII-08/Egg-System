<?php

namespace App\Exports;

use App\Models\SaleItem;
use App\Models\EggProduct;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SalesSummaryExport implements FromCollection, WithHeadings, WithCustomStartCell, WithStyles, ShouldAutoSize
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
        return 'A9'; // Start from row 9 to allow for header
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        // Get egg products in the correct order (excluding DAMAGED EGGS)
        $eggProducts = \App\Models\EggProduct::where('name', '!=', 'DAMAGED')
            ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
            ->orderByRaw("CASE 
                WHEN name = 'SMALL' THEN 1
                WHEN name = 'MEDIUM' THEN 2
                WHEN name = 'LARGE' THEN 3
                WHEN name = 'X-LARGE' THEN 4
                WHEN name = 'JUMBO' THEN 5
                WHEN name = 'PULLETS' THEN 6
                ELSE 7
            END")
            ->get();
        
        $productNames = $eggProducts->pluck('name')->toArray();
        
        // Fetch sales data grouped by date and product
        $salesData = SaleItem::with('product')
            ->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [
                    $startDate->copy()->startOfDay()->toDateTimeString(),
                    $endDate->copy()->endOfDay()->toDateTimeString()
                ]);
            })
            ->join('sales_transactions', 'sale_items.sales_transaction_id', '=', 'sales_transactions.id')
            ->selectRaw('DATE(sales_transactions.created_at) as sale_date, 
                         sale_items.egg_product_id,
                         SUM(sale_items.quantity * sale_items.price) as daily_revenue,
                         SUM(sale_items.quantity) as daily_quantity')
            ->groupBy('sale_date', 'sale_items.egg_product_id')
            ->get();
        
        // Generate all dates in the range
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        // Group sales data by date
        $groupedByDate = $salesData->groupBy('sale_date');
        
        // Build daily report rows
        $reportRows = collect([]);
        $columnTotals = [];
        
        // Initialize column totals
        foreach ($productNames as $productName) {
            $columnTotals[$productName] = ['quantity' => 0, 'revenue' => 0];
        }
        $columnTotals['total_eggs'] = 0;
        $columnTotals['total_sales'] = 0;
        
        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $dayNumber = $date->day;
            
            $row = ['day' => $dayNumber];
            
            // Initialize all product columns to 0 for this date
            foreach ($productNames as $productName) {
                $row[$productName] = 0; // Store revenue for daily rows
            }
            $row['total_eggs'] = 0;
            $row['total_sales'] = 0;
            
            // Fill in the revenue for products sold on this date
            if (isset($groupedByDate[$dateKey])) {
                foreach ($groupedByDate[$dateKey] as $item) {
                    $productName = $item->product->name ?? '';
                    if (in_array($productName, $productNames)) {
                        $row[$productName] = (float)$item->daily_revenue;
                        $row['total_eggs'] += (int)$item->daily_quantity;
                        $row['total_sales'] += (float)$item->daily_revenue;
                        
                        // Add to column totals
                        $columnTotals[$productName]['quantity'] += (int)$item->daily_quantity;
                        $columnTotals[$productName]['revenue'] += (float)$item->daily_revenue;
                    }
                }
            }
            
            $columnTotals['total_eggs'] += $row['total_eggs'];
            $columnTotals['total_sales'] += $row['total_sales'];
            
            $reportRows->push($row);
        }
        
        // Add totals row - show quantities in egg size columns for totals
        $totalsRow = ['day' => 'TOTAL'];
        foreach ($productNames as $productName) {
            $totalsRow[$productName] = $columnTotals[$productName]['quantity'];
        }
        $totalsRow['total_eggs'] = $columnTotals['total_eggs'];
        $totalsRow['total_sales'] = $columnTotals['total_sales'];
        $reportRows->push($totalsRow);

        return $reportRows;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $eggProducts = \App\Models\EggProduct::where('name', '!=', 'DAMAGED')
            ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
            ->orderByRaw("CASE 
                WHEN name = 'SMALL' THEN 1
                WHEN name = 'MEDIUM' THEN 2
                WHEN name = 'LARGE' THEN 3
                WHEN name = 'X-LARGE' THEN 4
                WHEN name = 'JUMBO' THEN 5
                WHEN name = 'PULLETS' THEN 6
                ELSE 7
            END")
            ->pluck('name')
            ->toArray();
        
        return array_merge(['DAYS'], $eggProducts, ['EGGS', 'SALES']);
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $startDate = Carbon::parse($this->startDate);
        $monthYear = $startDate->format('F, Y');
        
        // Get egg products in the correct order
        $eggProducts = \App\Models\EggProduct::where('name', '!=', 'DAMAGED')
            ->whereRaw('LOWER(name) != ?', ['damaged eggs'])
            ->orderByRaw("CASE 
                WHEN name = 'SMALL' THEN 1
                WHEN name = 'MEDIUM' THEN 2
                WHEN name = 'LARGE' THEN 3
                WHEN name = 'X-LARGE' THEN 4
                WHEN name = 'JUMBO' THEN 5
                WHEN name = 'PULLETS' THEN 6
                ELSE 7
            END")
            ->get();
        
        $productNames = $eggProducts->pluck('name')->toArray();
        $productCount = count($productNames);
        
        // Calculate column letters (A=DAYS, B onwards for products)
        $daysCol = 'A';
        $startCol = 'B'; // Start after DAYS column
        $endCol = chr(ord($startCol) + $productCount - 1);
        $totalStartCol = chr(ord($endCol) + 1);
        $totalEndCol = chr(ord($totalStartCol) + 1);
        $lastDataCol = $totalEndCol;
        
        // Set header row 6: "EGG SALES" (merged across DAYS + all products)
        $eggSalesEndCol = $endCol;
        $sheet->setCellValue($daysCol . '6', 'EGG SALES');
        $sheet->mergeCells($daysCol . '6:' . $eggSalesEndCol . '6');
        $sheet->getStyle($daysCol . '6')->getFont()->setBold(true);
        $sheet->getStyle($daysCol . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set header row 6: "TOTAL"
        $sheet->setCellValue($totalStartCol . '6', 'TOTAL');
        $sheet->mergeCells($totalStartCol . '6:' . $totalEndCol . '6');
        $sheet->getStyle($totalStartCol . '6')->getFont()->setBold(true);
        $sheet->getStyle($totalStartCol . '6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set prices row 7 (empty for DAYS column, then prices)
        $sheet->setCellValue($daysCol . '7', ''); // Empty for DAYS
        $col = $startCol;
        foreach ($eggProducts as $product) {
            $sheet->setCellValue($col . '7', $product->price);
            $col = chr(ord($col) + 1);
        }
        
        // Set column headers row 8: DAYS + product names + EGGS + SALES
        $sheet->setCellValue($daysCol . '8', 'DAYS');
        $col = $startCol;
        foreach ($productNames as $productName) {
            $sheet->setCellValue($col . '8', $productName);
            $col = chr(ord($col) + 1);
        }
        $sheet->setCellValue($totalStartCol . '8', 'EGGS');
        $sheet->setCellValue($totalEndCol . '8', 'SALES');
        $sheet->getStyle($daysCol . '8:' . $totalEndCol . '8')->getFont()->setBold(true);
        $sheet->getStyle($daysCol . '8:' . $totalEndCol . '8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the column headers (row 9 - this is where data starts)
        $sheet->getStyle($daysCol . '9:' . $lastDataCol . '9')->getFont()->setBold(true);
        $sheet->getStyle($daysCol . '9:' . $lastDataCol . '9')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($daysCol . '9:' . $lastDataCol . '9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the TOTAL row
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle($daysCol . $lastRow . ':' . $lastDataCol . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle($daysCol . $lastRow . ':' . $lastDataCol . $lastRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD3D3D3');
        
        return [];
    }
}