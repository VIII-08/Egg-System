<?php

namespace App\Exports;

use App\Models\ProductionLog;
use App\Models\ChickenStockLog;
use App\Models\FarmStat;
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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InventoryReportExport implements FromCollection, WithHeadings, WithCustomStartCell, WithStyles, ShouldAutoSize
{
    protected $startDate;
    protected $endDate;

    /** @var array<int, string> Cached column names from DB (e.g. PULLETS, SMALL, LARGE) */
    protected $columnNames = [];

    /** @var array<string, string> Map uppercase product name -> column name */
    protected $eggSizeMap = [];

    /** @var bool Whether buildMappings() has already run */
    protected $mappingsBuilt = false;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Build column names and egg size map from current egg_products (so renames don't break the report).
     */
    protected function buildMappings(): void
    {
        if ($this->mappingsBuilt) {
            return;
        }
        $this->mappingsBuilt = true;
        $allEggProducts = EggProduct::whereRaw('LOWER(name) != ?', ['damaged eggs'])
            ->orderByRaw("CASE 
                WHEN name = 'SMALL' THEN 1
                WHEN name = 'MEDIUM' THEN 2
                WHEN name = 'LARGE' THEN 3
                WHEN name = 'X-LARGE' THEN 4
                WHEN name = 'XL' THEN 4
                WHEN name = 'JUMBO' THEN 5
                WHEN name = 'PULLETS' THEN 6
                ELSE 7
            END")
            ->get();
        $damagedProduct = EggProduct::whereRaw('LOWER(name) LIKE ?', ['%damage%'])->first();

        foreach ($allEggProducts as $product) {
            $productNameUpper = strtoupper($product->name);
            $columnName = in_array($productNameUpper, ['XL', 'X-LARGE']) ? 'X-LARGE' : $productNameUpper;
            $this->eggSizeMap[$productNameUpper] = $columnName;
            if (!in_array($columnName, $this->columnNames)) {
                $this->columnNames[] = $columnName;
            }
        }
        if ($damagedProduct) {
            $damagedNameUpper = strtoupper($damagedProduct->name);
            $this->eggSizeMap[$damagedNameUpper] = $damagedNameUpper;
            if (!in_array($damagedNameUpper, $this->columnNames)) {
                $this->columnNames[] = $damagedNameUpper;
            }
        }
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
        $this->buildMappings();
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $productionLogs = ProductionLog::with('eggProduct')
            ->whereBetween('log_date', [$startDate, $endDate])
            ->get();

        $logsByDate = [];
        foreach ($productionLogs as $log) {
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

        $currentStock = FarmStat::where('stat_key', 'current_chicken_stock')->value('stat_value') ?? 0;
        $stockAdjustments = ChickenStockLog::whereDate('created_at', '<=', $endDate->endOfDay())
            ->orderBy('created_at', 'asc')
            ->get();

        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }

        $stockByDate = [];
        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $stockCount = $currentStock;
            foreach ($stockAdjustments as $adjustment) {
                $adjustmentDate = Carbon::parse($adjustment->created_at)->format('Y-m-d');
                if ($adjustmentDate > $dateKey) {
                    if ($adjustment->adjustment_type === 'addition') {
                        $stockCount -= $adjustment->quantity;
                    } else {
                        $stockCount += $adjustment->quantity;
                    }
                }
            }
            $stockByDate[$dateKey] = max(0, $stockCount);
        }

        $totals = array_fill_keys($this->columnNames, 0);
        $dailyReport = collect([]);

        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $row = [
                'day' => $date->day,
                'hens' => $stockByDate[$dateKey] ?? $currentStock,
            ];
            foreach ($this->columnNames as $col) {
                $row[$col] = 0;
            }
            if (isset($logsByDate[$dateKey])) {
                foreach ($logsByDate[$dateKey] as $productName => $quantity) {
                    $columnName = $this->eggSizeMap[$productName] ?? null;
                    if ($columnName !== null && isset($row[$columnName])) {
                        $row[$columnName] = $quantity;
                        $totals[$columnName] += $quantity;
                    }
                }
            }
            $dailyReport->push($row);
        }

        $totalsRow = ['day' => 'TOTAL', 'hens' => ''];
        foreach ($this->columnNames as $col) {
            $totalsRow[$col] = $totals[$col] ?? 0;
        }
        $dailyReport->push($totalsRow);

        return $dailyReport;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        $this->buildMappings();
        return array_merge(['DAYS', 'HENS'], $this->columnNames);
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        $this->buildMappings();
        $monthYear = Carbon::parse($this->startDate)->format('F, Y');
        $lastColIndex = 2 + count($this->columnNames);
        $lastColLetter = Coordinate::stringFromColumnIndex($lastColIndex);
        $range4 = 'A4:' . $lastColLetter . '4';
        $lastRow = $sheet->getHighestRow();
        $totalRange = 'A' . $lastRow . ':' . $lastColLetter . $lastRow;

        $sheet->setCellValue('A1', strtoupper($monthYear));
        $sheet->mergeCells('A1:' . $lastColLetter . '1');
        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'EGG PRODUCTION (Grams)');
        $sheet->mergeCells('A2:' . $lastColLetter . '2');
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('B3', 'HENS');
        $sheet->getStyle('A3:' . $lastColLetter . '3')->getFont()->setBold(true);
        $sheet->getStyle('A3:' . $lastColLetter . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($range4)->getFont()->setBold(true);
        $sheet->getStyle($range4)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($range4)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($totalRange)->getFont()->setBold(true);
        $sheet->getStyle($totalRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD3D3D3');

        return [];
    }
}

