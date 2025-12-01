<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Staff\ProductionDashboardController;
use App\Http\Controllers\Staff\ProductionLogController;
use App\Http\Controllers\Staff\ChickenStockController;
use App\Http\Controllers\Staff\ExpenseController;
use App\Http\Controllers\Staff\DataCorrectionController;
use App\Http\Controllers\Staff\RecordViewController;
use App\Http\Controllers\Staff\MarketingDashboardController;
use App\Http\Controllers\Staff\ForecastingController as StaffForecastingController;
use App\Http\Controllers\Staff\SalesController;
use App\Http\Controllers\Treasurer\TreasurerDashboardController;
use App\Http\Controllers\Treasurer\RecordViewController as TreasurerRecordViewController;
use App\Http\Controllers\Treasurer\FinancialReportController;
use App\Http\Controllers\Treasurer\ForecastingController as TreasurerForecastingController;
use App\Http\Controllers\Admin\RecordViewController as AdminRecordViewController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ForecastingController;
use App\Http\Controllers\Admin\ExpenseCategoryController as AdminExpenseCategoryController;
use App\Http\Controllers\Admin\EggProductController as AdminEggProductController;
use App\Http\Controllers\Treasurer\ExpenseCategoryController as TreasurerExpenseCategoryController;
use App\Http\Controllers\Treasurer\EggProductController as TreasurerEggProductController;

// All web routes should be within this group to get session, etc.
Route::middleware('web')->group(function () {

    // --- Guest Route ---
    Route::get('/', function () {
        return redirect()->route('login');
    });

    // --- Authenticated Routes ---
    Route::middleware('auth')->group(function () {

        // Default generic dashboard
        Route::get('/dashboard', function () {
            return inertia('Dashboard');
        })->name('dashboard');

        // Profile management
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // --- ADMIN ROUTES ---
        Route::middleware('role:admin')
            ->prefix('admin')
            ->name('admin.')
            ->group(function () {
                Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
                Route::get('/records', [AdminRecordViewController::class, 'index'])->name('records.index');
                Route::get('/financial-reports/{id}/view', [AdminRecordViewController::class, 'viewFinancialReport'])->name('financial-reports.view');
                Route::get('/financial-reports/{id}/download', [AdminRecordViewController::class, 'downloadFinancialReport'])->name('financial-reports.download');
                Route::resource('users', UserManagementController::class)->except(['show']);
                Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
                Route::patch('/approvals/{id}', [ApprovalController::class, 'update'])->name('approvals.update');
                Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
                Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
                Route::get('/reports/download', [ReportController::class, 'downloadPdf'])->name('reports.download');
                Route::get('/reports/download-excel', [ReportController::class, 'downloadExcel'])->name('reports.download.excel');
                Route::get('/expense-categories', [AdminExpenseCategoryController::class, 'index'])->name('expense-categories.index');
                Route::post('/expense-categories', [AdminExpenseCategoryController::class, 'store'])->name('expense-categories.store');
                Route::delete('/expense-categories/{expenseCategory}', [AdminExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
                Route::get('/egg-products', [AdminEggProductController::class, 'index'])->name('egg-products.index');
                Route::post('/egg-products', [AdminEggProductController::class, 'store'])->name('egg-products.store');
                Route::put('/egg-products/{egg_product}', [AdminEggProductController::class, 'update'])->name('egg-products.update');
                Route::delete('/egg-products/{egg_product}', [AdminEggProductController::class, 'destroy'])->name('egg-products.destroy');
                Route::get('/forecasting', [App\Http\Controllers\Admin\ForecastingController::class, 'index'])->name('forecasting.index');
            });

       // ** TREASURER ROUTES **
        Route::middleware(['auth', 'role:treasurer'])->prefix('treasurer')->name('treasurer.')->group(function () {
            Route::get('/dashboard', [TreasurerDashboardController::class, 'index'])->name('dashboard');
            Route::get('/expense-categories', [TreasurerExpenseCategoryController::class, 'index'])->name('expense-categories.index');
            Route::post('/expense-categories', [TreasurerExpenseCategoryController::class, 'store'])->name('expense-categories.store');
            Route::delete('/expense-categories/{expenseCategory}', [TreasurerExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
            Route::get('/egg-products', [TreasurerEggProductController::class, 'index'])->name('egg-products.index');
            Route::post('/egg-products', [TreasurerEggProductController::class, 'store'])->name('egg-products.store');
            Route::put('/egg-products/{egg_product}', [TreasurerEggProductController::class, 'update'])->name('egg-products.update');
            Route::delete('/egg-products/{egg_product}', [TreasurerEggProductController::class, 'destroy'])->name('egg-products.destroy');
            Route::get('/forecasting', [TreasurerForecastingController::class, 'index'])->name('forecasting.index');
            Route::get('/records', [TreasurerRecordViewController::class, 'index'])->name('records.index');
            Route::get('/financial-reports', [FinancialReportController::class, 'index'])->name('reports.index');
            Route::post('/financial-reports', [FinancialReportController::class, 'store'])->name('reports.store');
            Route::get('/financial-reports/{id}/view', [FinancialReportController::class, 'view'])->name('reports.view');
            Route::get('/financial-reports/{id}/download', [FinancialReportController::class, 'download'])->name('reports.download');
            Route::get('/financial-reports/{id}/print', [FinancialReportController::class, 'print'])->name('reports.print');
        });

        // --- UNIFIED STAFF ROUTES ---
        Route::middleware(['auth', 'role:staff-production,staff-marketing'])
            ->prefix('staff')
            ->group(function () {

                // PRODUCTION DASHBOARD (only for staff-production)
                Route::get('/production-dashboard', [ProductionDashboardController::class, 'index'])
                    ->middleware('role:staff-production')
                    ->name('production.dashboard');

                // MARKETING DASHBOARD (only for staff-marketing)
                Route::get('/marketing-dashboard', [MarketingDashboardController::class, 'index'])
                    ->middleware('role:staff-marketing')
                    ->name('marketing.dashboard');

                // --- PRODUCTION-ONLY ROUTES ---
                Route::middleware('role:staff-production')->group(function () {
                    Route::get('/production-forecasting', [StaffForecastingController::class, 'production'])->name('production.forecasting');

                    Route::get('/log-production', [ProductionLogController::class, 'create'])->name('production.logs.create');
                    Route::post('/log-production', [ProductionLogController::class, 'store'])->name('production.logs.store');

                    Route::get('/chicken-stock', [ChickenStockController::class, 'index'])->name('chicken.stock.index');
                    Route::post('/chicken-stock', [ChickenStockController::class, 'store'])->name('chicken.stock.store');
                });

                // --- MARKETING-ONLY ROUTES ---
                Route::middleware('role:staff-marketing')->group(function () {
                    Route::get('/marketing-forecasting', [StaffForecastingController::class, 'marketing'])->name('marketing.forecasting');

                    Route::get('/record-sale', [SalesController::class, 'create'])->name('sales.create');
                    Route::post('/record-sale', [SalesController::class, 'store'])->name('sales.store');
                });

                // --- SHARED STAFF ROUTES (both roles can access) ---
                Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
                Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');

                Route::get('/my-records', [RecordViewController::class, 'index'])->name('records.index');

                Route::get('/data-correction/create', [DataCorrectionController::class, 'create'])->name('data-correction.create');
                Route::post('/data-correction', [DataCorrectionController::class, 'store'])->name('data-correction.store');
            });
    });

    // Auth routes
    require __DIR__.'/auth.php';
});
