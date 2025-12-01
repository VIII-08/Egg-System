<?php

namespace App\Exports;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExpenseReportExport implements FromCollection, WithHeadings, ShouldAutoSize, WithCustomStartCell, WithStyles, WithTitle
{
    protected $startDate;
    protected $endDate;
    protected $categoryNames;
    protected $monthYear;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        
        // Get ALL expense categories from the database (not just those with expenses)
        $this->categoryNames = ExpenseCategory::orderBy('name')
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();
        
        $this->monthYear = Carbon::parse($startDate)->format('F, Y');
    }

    public function startCell(): string
    {
        return 'A5'; // Data starts from row 5
    }

    public function title(): string
    {
        return 'RTL1,' . $this->monthYear;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        // Get all expenses in the date range
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->get();
        
        // Group expenses by date and category
        $expensesByDate = [];
        foreach ($expenses as $expense) {
            $dateKey = Carbon::parse($expense->expense_date)->format('Y-m-d');
            $category = $expense->category ?? 'Uncategorized';
            
            if (!isset($expensesByDate[$dateKey])) {
                $expensesByDate[$dateKey] = [];
            }
            if (!isset($expensesByDate[$dateKey][$category])) {
                $expensesByDate[$dateKey][$category] = 0;
            }
            $expensesByDate[$dateKey][$category] += $expense->amount;
        }
        
        // Generate all dates in the range
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->copy();
            $currentDate->addDay();
        }
        
        // Build daily report
        $dailyReport = collect([]);
        $columnTotals = [];
        
        // Initialize column totals
        foreach ($this->categoryNames as $categoryName) {
            $columnTotals[$categoryName] = 0;
        }
        $columnTotals['total_expenses'] = 0;
        
        foreach ($dates as $date) {
            $dateKey = $date->format('Y-m-d');
            $dayNumber = $date->day;
            
            $row = ['day' => $dayNumber];
            
            // Initialize all category columns to 0 for this date
            foreach ($this->categoryNames as $categoryName) {
                $row[$categoryName] = 0;
            }
            $row['total_expenses'] = 0;
            
            // Fill in the expenses for categories on this date
            if (isset($expensesByDate[$dateKey])) {
                foreach ($expensesByDate[$dateKey] as $category => $amount) {
                    if (in_array($category, $this->categoryNames)) {
                        $row[$category] = (float)$amount;
                        $row['total_expenses'] += (float)$amount;
                        
                        // Add to column totals
                        $columnTotals[$category] += (float)$amount;
                    }
                }
            }
            
            $columnTotals['total_expenses'] += $row['total_expenses'];
            
            // Format for Excel output
            $formattedRow = [$row['day']];
            foreach ($this->categoryNames as $categoryName) {
                $formattedRow[] = $row[$categoryName];
            }
            $formattedRow[] = $row['total_expenses'];
            $dailyReport->push($formattedRow);
        }
        
        // Add totals row
        $totalsRow = ['TOTAL'];
        foreach ($this->categoryNames as $categoryName) {
            $totalsRow[] = $columnTotals[$categoryName];
        }
        $totalsRow[] = $columnTotals['total_expenses'];
        $dailyReport->push($totalsRow);
        
        return $dailyReport;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return array_merge(['DAYS'], $this->categoryNames, ['TOTAL']);
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        $numCategories = count($this->categoryNames);
        $lastCategoryCol = chr(ord('A') + $numCategories); // e.g., if 5 categories, F
        $totalCol = chr(ord('A') + $numCategories + 1); // G
        
        // Set A1 to Month, Year
        $sheet->setCellValue('A1', $this->monthYear);
        $sheet->getStyle('A1')->getFont()->setBold(true);
        
        // Set header row 3: "EXPENSES"
        $sheet->setCellValue('A3', 'EXPENSES');
        $sheet->mergeCells('A3:' . $lastCategoryCol . '3');
        $sheet->getStyle('A3')->getFont()->setBold(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set header row 3: "TOTAL"
        $sheet->setCellValue($totalCol . '3', 'TOTAL');
        $sheet->getStyle($totalCol . '3')->getFont()->setBold(true);
        $sheet->getStyle($totalCol . '3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Set column headers row 4
        $colIndex = 0;
        $sheet->setCellValue('A4', 'DAYS');
        $colIndex = 1;
        foreach ($this->categoryNames as $categoryName) {
            $colLetter = chr(ord('A') + $colIndex);
            $sheet->setCellValue($colLetter . '4', $categoryName);
            $colIndex++;
        }
        $sheet->setCellValue($totalCol . '4', 'TOTAL');
        $sheet->getStyle('A4:' . $totalCol . '4')->getFont()->setBold(true);
        $sheet->getStyle('A4:' . $totalCol . '4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the column headers (row 5, which is headings)
        $sheet->getStyle('A5:' . $totalCol . '5')->getFont()->setBold(true);
        $sheet->getStyle('A5:' . $totalCol . '5')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0'); // Light grey
        $sheet->getStyle('A5:' . $totalCol . '5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Style the TOTAL row
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A' . $lastRow . ':' . $totalCol . $lastRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $lastRow . ':' . $totalCol . $lastRow)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD3D3D3'); // Darker grey
        $sheet->getStyle('A' . $lastRow . ':' . $totalCol . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Auto size columns
        foreach (range('A', $totalCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return [];
    }
}