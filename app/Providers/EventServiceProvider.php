<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// --- Import ALL models and observers you are using ---
use App\Models\Expense;
use App\Observers\ExpenseObserver;
use App\Models\SalesTransaction;
use App\Observers\SalesTransactionObserver;
use App\Models\ProductionLog;
use App\Observers\ProductionLogObserver;
use App\Models\DataCorrectionRequest;
use App\Observers\DataCorrectionRequestObserver;
use App\Models\FinancialReport;
use App\Observers\FinancialReportObserver;
use App\Models\ChickenStockLog;         
use App\Observers\ChickenStockLogObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     * (This part is not changed)
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     * We are now LEAVING THIS EMPTY as a failsafe.
     * @var array
     */
    protected $observers = [
        //
    ];

    /**
     * Register any events for your application.
     * THIS IS THE NEW, EXPLICIT REGISTRATION LOGIC.
     */
    public function boot(): void
    {
        Expense::observe(ExpenseObserver::class);
        SalesTransaction::observe(SalesTransactionObserver::class);
        ProductionLog::observe(ProductionLogObserver::class);
        DataCorrectionRequest::observe(DataCorrectionRequestObserver::class);
        FinancialReport::observe(FinancialReportObserver::class);
        ChickenStockLog::observe(ChickenStockLogObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}