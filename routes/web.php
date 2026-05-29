<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Existing controllers
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\Auth\LoginController;

// New controllers (create these as you build each module)
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BulkImportController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout',[LoginController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    /*
    |------------------------------------------------------------------
    | Overview
    |------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    /*
    |------------------------------------------------------------------
    | Finance — Banking
    |------------------------------------------------------------------
    */
    Route::get('/banking', [BankingController::class, 'index'])->name('banking.index');
    Route::post('/banking', [BankingController::class, 'store'])->name('banking.store');
    Route::put('/banking/{deposit}', [BankingController::class, 'update'])->name('banking.update');
    Route::post('/banking/{deposit}/confirm', [BankingController::class, 'confirm'])->name('banking.confirm');

    /*
    |------------------------------------------------------------------
    | Collections — Transactions
    |------------------------------------------------------------------
    */
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/',        [TransactionController::class, 'index'])->name('index');
        Route::get('/{payment}', [TransactionController::class, 'show'])->name('show');
        Route::post('/',       [TransactionController::class, 'store'])->name('store');
        Route::get('/export',  [TransactionController::class, 'export'])->name('export');
    });

    /*
    |------------------------------------------------------------------
    | Collections — Clients
    |------------------------------------------------------------------
    */
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/',              [ClientController::class, 'index'])->name('index');
        Route::get('/create',        [ClientController::class, 'create'])->name('create');
        Route::post('/',             [ClientController::class, 'store'])->name('store');
        Route::get('/{client}',      [ClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}',      [ClientController::class, 'update'])->name('update');
        Route::delete('/{client}',   [ClientController::class, 'destroy'])->name('destroy');
        // Client payment history sub-page
        Route::get('/{client}/payments', [ClientController::class, 'payments'])->name('payments');
    });

    /*
    |------------------------------------------------------------------
    | Collections — Debts & Penalties
    |------------------------------------------------------------------
    */
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/',                  [DebtController::class, 'index'])->name('index');
        Route::get('/{debt}',            [DebtController::class, 'show'])->name('show');
        Route::post('/apply-penalties',  [DebtController::class, 'applyPenalties'])->name('apply-penalties');
        Route::post('/{debt}/write-off', [DebtController::class, 'writeOff'])->name('write-off');
        Route::get('/export',            [DebtController::class, 'export'])->name('export');
    });

    /*
    |------------------------------------------------------------------
    | Collections — Schedule
    |------------------------------------------------------------------
    */
    Route::prefix('schedule')->name('schedule.')->group(function () {
        Route::get('/',              [ScheduleController::class, 'index'])->name('index');
        Route::post('/',             [ScheduleController::class, 'store'])->name('store');
        Route::put('/{schedule}',    [ScheduleController::class, 'update'])->name('update');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });

    /*
    |------------------------------------------------------------------
    | Collections — Bulk Import
    |------------------------------------------------------------------
    */
    Route::prefix('bulk-import')->name('bulk-import.')->group(function () {
        Route::get('/',                  [BulkImportController::class, 'index'])->name('index');
        Route::post('/',                 [BulkImportController::class, 'store'])->name('store');
        Route::get('/download-template', [BulkImportController::class, 'downloadTemplate'])->name('download-template');
    });


    /*
    |------------------------------------------------------------------
    | Finance — Expenses
    |------------------------------------------------------------------
    */
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/',              [ExpenseController::class, 'index'])->name('index');
        Route::get('/create',        [ExpenseController::class, 'create'])->name('create');
        Route::post('/',             [ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}/edit',[ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{expense}',     [ExpenseController::class, 'update'])->name('update');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::post('/{expense}/reject',  [ExpenseController::class, 'reject'])->name('reject');
    });

    /*
    |------------------------------------------------------------------
    | Finance — Payroll (your existing routes kept intact)
    |------------------------------------------------------------------
    */
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/',                              [PayrollController::class, 'index'])->name('index');
        Route::get('/generate',                      [PayrollController::class, 'generate'])->name('generate');
        Route::post('/',                             [PayrollController::class, 'store'])->name('store');
        Route::post('/{salaryPayment}/mark-paid',    [PayrollController::class, 'markAsPaid'])->name('mark-paid');
    });

    /*
    |------------------------------------------------------------------
    | Finance — General / Tax Reports (your existing kept + extras)
    |------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/',             [ReportsController::class, 'index'])->name('index');
        Route::post('/generate',    [ReportsController::class, 'generate'])->name('generate');
        Route::get('/monthly',      [ReportsController::class, 'monthly'])->name('monthly');
        Route::get('/yearly',       [ReportsController::class, 'yearly'])->name('yearly');
        Route::get('/collector',    [ReportsController::class, 'collector'])->name('collector');
        Route::get('/export-pdf',   [ReportsController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-excel', [ReportsController::class, 'exportExcel'])->name('export-excel');
    });

    /*
    |------------------------------------------------------------------
    | HR & Operations — Staff (your existing routes kept intact)
    |------------------------------------------------------------------
    */
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/',           [StaffController::class, 'index'])->name('index');
        Route::get('/create',     [StaffController::class, 'create'])->name('create');
        Route::post('/',          [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}',    [StaffController::class, 'show'])->name('show');
        Route::get('/{staff}/edit',[StaffController::class, 'edit'])->name('edit');
        Route::put('/{staff}',    [StaffController::class, 'update'])->name('update');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy');
    });

    /*
    |------------------------------------------------------------------
    | HR & Operations — Attendance (your existing routes kept intact)
    |------------------------------------------------------------------
    */
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/',         [AttendanceController::class, 'index'])->name('index');
        Route::post('/',        [AttendanceController::class, 'store'])->name('store');
        Route::post('/bulk',    [AttendanceController::class, 'bulkStore'])->name('bulk');
        Route::get('/monthly',  [AttendanceController::class, 'monthly'])->name('monthly');
    });

    /*
    |------------------------------------------------------------------
    | HR & Operations — Vehicles
    |------------------------------------------------------------------
    */
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/',              [VehicleController::class, 'index'])->name('index');
        Route::get('/create',        [VehicleController::class, 'create'])->name('create');
        Route::post('/',             [VehicleController::class, 'store'])->name('store');
        Route::get('/{vehicle}',     [VehicleController::class, 'show'])->name('show');
        Route::get('/{vehicle}/edit',[VehicleController::class, 'edit'])->name('edit');
        Route::put('/{vehicle}',     [VehicleController::class, 'update'])->name('update');
    });

    /*
    |------------------------------------------------------------------
    | System — RBAC & Roles
    |------------------------------------------------------------------
    */
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/',           [RoleController::class, 'index'])->name('index');
        Route::post('/',          [RoleController::class, 'store'])->name('store');
        Route::put('/{role}',     [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}',  [RoleController::class, 'destroy'])->name('destroy');
        // Assign role to user
        Route::post('/assign',    [RoleController::class, 'assign'])->name('assign');
        Route::post('/revoke',    [RoleController::class, 'revoke'])->name('revoke');
    });

    /*
    |------------------------------------------------------------------
    | System — Audit Log
    |------------------------------------------------------------------
    */
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/',         [AuditController::class, 'index'])->name('index');
        Route::get('/export',   [AuditController::class, 'export'])->name('export');
    });

    /*
    |------------------------------------------------------------------
    | System — Settings
    |------------------------------------------------------------------
    */
    Route::get('/settings',       [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings',       [SettingsController::class, 'update'])->name('settings.update');

    /*
    |------------------------------------------------------------------
    | Finance (legacy route kept for backward compatibility)
    |------------------------------------------------------------------
    */
    Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

    /*
    |------------------------------------------------------------------
    | Payments (used by Debt partial payment form)
    |------------------------------------------------------------------
    */
    Route::get('/payments/create', [TransactionController::class, 'create'])->name('payments.create');

});