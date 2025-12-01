<?php
namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesTransaction;
use App\Models\Expense;
use App\Models\FinancialReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TreasurerDashboardController extends Controller
{
    public function index()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // --- KPI Card Metrics (This Month) ---
        $revenueThisMonth = SalesTransaction::whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $expensesThisMonth = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $netIncomeThisMonth = $revenueThisMonth - $expensesThisMonth;
        
        // --- Monthly Income vs. Expenses Bar Chart (Last 5 Months) ---
        $barChartData = $this->prepareBarChartData();

        // --- Expense Breakdown Pie Chart (This Month) ---
        $pieChartData = $this->preparePieChartData($startOfMonth, $endOfMonth);
        
        // --- Financial Report Notifications (Latest approved/rejected reports) ---
        $latestReportNotification = FinancialReport::where('generated_by', Auth::id())
            ->whereIn('status', ['approved', 'rejected'])
            ->latest('reviewed_at')
            ->first();
        
        return inertia('Treasurer/Dashboard', [
            'revenueThisMonth' => (float) $revenueThisMonth,
            'expensesThisMonth' => (float) $expensesThisMonth,
            'netIncomeThisMonth' => (float) $netIncomeThisMonth,
            'barChartData' => $barChartData,
            'pieChartData' => $pieChartData,
            'latestReportNotification' => $latestReportNotification ? [
                'id' => $latestReportNotification->id,
                'status' => $latestReportNotification->status,
                'start_date' => $latestReportNotification->start_date,
                'end_date' => $latestReportNotification->end_date,
                'admin_notes' => $latestReportNotification->admin_notes,
                'reviewed_at' => $latestReportNotification->reviewed_at,
            ] : null,
        ]);
    }

    private function prepareBarChartData()
    {
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths(4)->startOfMonth();

        $sales = SalesTransaction::whereBetween('created_at', [$startDate, $endDate])->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw("SUM(total_amount) as total"))->groupBy('month')->pluck('total', 'month');
        $expenses = Expense::whereBetween('expense_date', [$startDate, $endDate])->select(DB::raw("DATE_FORMAT(expense_date, '%Y-%m') as month"), DB::raw("SUM(amount) as total"))->groupBy('month')->pluck('total', 'month');
        
        $labels = []; $salesData = []; $expensesData = [];
        for ($i = 0; $i < 5; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $monthKey = $month->format('Y-m');
            $labels[] = $month->format('F');
            $salesData[] = $sales[$monthKey] ?? 0;
            $expensesData[] = $expenses[$monthKey] ?? 0;
        }
        return ['labels' => $labels, 'sales' => $salesData, 'expenses' => $expensesData];
    }

    private function preparePieChartData($startOfMonth, $endOfMonth)
    {
        // Simplified categories to match the pie chart
        $categoryMapping = [
            'Feeds' => 'Poultry Feed',
            'Biologics' => 'Maintenance & Others',
            'Miscellaneous(tray)' => 'Maintenance & Others',
            'Electricity' => 'Utilities',
            'Water' => 'Utilities',
            'Repairs(building)' => 'Maintenance & Others',
            'Fuel' => 'Maintenance & Others', // Or its own category if significant
            'Transportation' => 'Maintenance & Others',
        ];

        $expenses = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->get();
        
        $groupedExpenses = $expenses->groupBy(function ($expense) use ($categoryMapping) {
            return $categoryMapping[$expense->category] ?? 'Maintenance & Others';
        })->map->sum('amount');

        // Hardcode Labor & Salaries for now, as it's not in our expense table
        $groupedExpenses['Labor & Salaries'] = 0; 
        
        return [
            'labels' => $groupedExpenses->keys()->toArray(),
            'data' => $groupedExpenses->values()->toArray(),
        ];
    }
}