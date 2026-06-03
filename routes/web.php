<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\BankingController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\BulkImportController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CollectionSessionController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/export', [DashboardController::class, 'exportDashboard'])->name('dashboard.export');
    Route::get('/dashboard/alerts', [DashboardController::class, 'getAlerts'])->name('dashboard.alerts');
    Route::get('/dashboard/flag-non-payers', [DashboardController::class, 'flagNonPayers'])->name('dashboard.flag-non-payers');
    Route::get('/dashboard/export-monthly', [DashboardController::class, 'exportMonthly'])->name('dashboard.export-monthly');

    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::post('/bulk-clock', [AttendanceController::class, 'bulkClock'])->name('bulk-clock');
        Route::get('/monthly-report', [AttendanceController::class, 'monthlyReport'])->name('monthly-report');
        Route::post('/leave-request', [AttendanceController::class, 'storeLeaveRequest'])->name('leave-request');
        Route::patch('/leave/{id}/approve', [AttendanceController::class, 'approveLeave'])->name('leave.approve');
        Route::patch('/leave/{id}/reject', [AttendanceController::class, 'rejectLeave'])->name('leave.reject');
    });

    // Transactions (Payments)
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
        Route::get('/create', [TransactionController::class, 'create'])->name('create');
        Route::post('/', [TransactionController::class, 'store'])->name('store');
        Route::get('/{payment}', [TransactionController::class, 'show'])->name('show');
        Route::post('/{payment}/refund', [TransactionController::class, 'refund'])->name('refund');
        Route::post('/{payment}/email-receipt', [TransactionController::class, 'sendReceiptEmail'])->name('email-receipt');
        Route::get('/import', [TransactionController::class, 'importPage'])->name('import');
        Route::post('/import/preview', [TransactionController::class, 'preview'])->name('import.preview');
        Route::post('/import/confirm', [TransactionController::class, 'confirmImport'])->name('import.confirm');
        Route::get('/export', [TransactionController::class, 'export'])->name('export');
        Route::get('/export/pdf', [TransactionController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/imported-pdf', [TransactionController::class, 'exportImportedPdf'])->name('export.imported-pdf');
        Route::get('/{payment}/pdf', [TransactionController::class, 'downloadPdf'])->name('pdf');
        Route::post('/reconcile-batch', [TransactionController::class, 'reconcileWithBank'])->name('reconcile-batch');
        Route::post('/export-batch', [TransactionController::class, 'exportBatch'])->name('export-batch');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::post('/generate', [ReportsController::class, 'generate'])->name('generate');
        Route::get('/monthly', [ReportsController::class, 'monthly'])->name('monthly');
        Route::get('/yearly', [ReportsController::class, 'yearly'])->name('yearly');
        Route::get('/collector', [ReportsController::class, 'collector'])->name('collector');
        Route::get('/export-pdf', [ReportsController::class, 'exportPdf'])->name('export-pdf');
        Route::get('/export-excel', [ReportsController::class, 'exportExcel'])->name('export-excel');
        Route::get('/download/{reportId}', [ReportsController::class, 'download'])->name('download');
        Route::get('/daily-collector', [ReportsController::class, 'dailyCollectorPerformance'])->name('daily-collector');
        Route::get('/daily-company', [ReportsController::class, 'dailyCompanyPerformance'])->name('daily-company');
        Route::get('/daily-routes', [ReportsController::class, 'dailyRoutesReport'])->name('daily-routes');
        Route::get('/weekly-collector', [ReportsController::class, 'weeklyCollectorPerformance'])->name('weekly-collector');
        Route::get('/weekly-company', [ReportsController::class, 'weeklyCompanyPerformance'])->name('weekly-company');
        Route::get('/weekly-financial', [ReportsController::class, 'weeklyFinancialReport'])->name('weekly-financial');
        Route::get('/weekly-waste', [ReportsController::class, 'weeklyWasteCollectionReport'])->name('weekly-waste');
        Route::post('/schedule', [ReportsController::class, 'scheduleReport'])->name('schedule');
        Route::post('/send-now/{report}', [ReportsController::class, 'sendNow'])->name('send-now');
        Route::get('/compare', [ReportsController::class, 'monthlyComparison'])->name('compare');
    });

    // Payroll
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::post('/generate', [PayrollController::class, 'generate'])->name('generate');
        Route::post('/store', [PayrollController::class, 'store'])->name('store');
        Route::patch('/{salaryPayment}/mark-paid', [PayrollController::class, 'markAsPaid'])->name('mark-paid');
        Route::get('/export', [PayrollController::class, 'export'])->name('export');
        Route::post('/process-all', [PayrollController::class, 'processPayments'])->name('process-all');
        Route::get('/{salaryPayment}/payslip', [PayrollController::class, 'generatePayslip'])->name('payslip');
        Route::post('/{salaryPayment}/email-payslip', [PayrollController::class, 'emailPayslip'])->name('email-payslip');
        Route::get('/export-bank-file', [PayrollController::class, 'exportBankFile'])->name('export-bank-file');
        Route::post('/advance-request', [PayrollController::class, 'requestAdvance'])->name('advance.request');
        Route::patch('/advance/{id}/approve', [PayrollController::class, 'approveAdvance'])->name('advance.approve');
    });

    // Staff Management
    Route::resource('staff', StaffController::class)->except(['create', 'edit']);
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::post('/{staff}/documents', [StaffController::class, 'uploadDocument'])->name('upload-document');
        Route::post('/{staff}/emergency-contact', [StaffController::class, 'addEmergencyContact'])->name('add-emergency-contact');
        Route::post('/{staff}/rate', [StaffController::class, 'ratePerformance'])->name('rate');
        Route::post('/bulk-import', [StaffController::class, 'bulkImport'])->name('bulk-import');
        Route::patch('/{staff}/archive', [StaffController::class, 'archive'])->name('archive');
        Route::patch('/archive/{id}/restore', [StaffController::class, 'restore'])->name('restore');
        Route::get('/export', [StaffController::class, 'export'])->name('export');
    });

    // Expenses
    Route::resource('expenses', ExpenseController::class)->except(['create', 'edit']);
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::patch('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::patch('/{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
        Route::get('/export', [ExpenseController::class, 'export'])->name('export');
        Route::get('/analytics', [ExpenseController::class, 'analytics'])->name('analytics');
    });

    // Banking & Deposits
    Route::prefix('banking')->name('banking.')->group(function () {
        Route::get('/', [BankingController::class, 'index'])->name('index');
        Route::post('/accounts', [BankingController::class, 'storeAccount'])->name('store-account');
        Route::post('/deposits', [BankingController::class, 'storeDeposit'])->name('store-deposit');
        Route::patch('/deposits/{deposit}', [BankingController::class, 'update'])->name('update-deposit');
        Route::patch('/deposits/{id}/confirm', [BankingController::class, 'confirm'])->name('confirm');
        Route::get('/export', [BankingController::class, 'export'])->name('export');
        Route::post('/reconcile', [BankingController::class, 'reconcile'])->name('reconcile');
        Route::post('/accounts/{bankAccount}/upload-statement', [BankingController::class, 'uploadStatement'])->name('upload-statement');
        Route::patch('/deposits/{deposit}/manual-reconcile', [BankingController::class, 'reconcileManual'])->name('manual-reconcile');
        Route::get('/cash-position', [BankingController::class, 'dailyCashPositionReport'])->name('cash-position');
    });

    // Audit Logs
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditController::class, 'index'])->name('index');
        Route::get('/{auditLog}', [AuditController::class, 'show'])->name('show');
        Route::post('/{auditLog}/restore', [AuditController::class, 'restore'])->name('restore');
        Route::delete('/cleanup', [AuditController::class, 'cleanup'])->name('cleanup');
        Route::get('/export', [AuditController::class, 'export'])->name('export');
    });

    // Roles & Permissions
    Route::resource('roles', RoleController::class)->except(['create', 'edit']);
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::post('/assign', [RoleController::class, 'assign'])->name('assign');
        Route::post('/revoke', [RoleController::class, 'revoke'])->name('revoke');
        Route::post('/{role}/clone', [RoleController::class, 'clone'])->name('clone');
        Route::post('/seed-defaults', [RoleController::class, 'seedDefaults'])->name('seed-defaults');
    });

    // Bulk Imports
    Route::prefix('bulk-import')->name('bulk-import.')->group(function () {
        Route::get('/', [BulkImportController::class, 'index'])->name('index');
        Route::post('/', [BulkImportController::class, 'store'])->name('store');
        Route::get('/{bulkImport}', [BulkImportController::class, 'show'])->name('show');
        Route::get('/template/{entityType}', [BulkImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/{bulkImport}/rollback', [BulkImportController::class, 'rollback'])->name('rollback');
    });

    // Vehicles
    Route::resource('vehicles', VehicleController::class);
    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::post('/{vehicle}/maintenance', [VehicleController::class, 'scheduleMaintenance'])->name('schedule-maintenance');
        Route::patch('/maintenance/{maintenance}/complete', [VehicleController::class, 'completeMaintenance'])->name('complete-maintenance');
        Route::post('/{vehicle}/fuel-log', [VehicleController::class, 'addFuelLog'])->name('add-fuel-log');
        Route::get('/export', [VehicleController::class, 'export'])->name('export');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
        Route::get('/export', [AnalyticsController::class, 'export'])->name('export');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
        Route::post('/test-email', [SettingsController::class, 'testEmail'])->name('test-email');
        Route::post('/clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('/backup', [SettingsController::class, 'runBackup'])->name('backup');
    });

    // Collection Schedules
    Route::resource('schedules', ScheduleController::class);
    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/plan/generate', [ScheduleController::class, 'generateWeeklyPlan'])->name('generate-plan');
    });

    // Debts & Collections
    Route::prefix('debts')->name('debts.')->group(function () {
        Route::get('/', [DebtController::class, 'index'])->name('index');
        Route::post('/client/{client}/payment-plan', [DebtController::class, 'createPaymentPlan'])->name('create-payment-plan');
        Route::post('/invoice/{invoice}/reminder', [DebtController::class, 'sendReminder'])->name('send-reminder');
        Route::patch('/invoice/{invoice}/write-off', [DebtController::class, 'writeOff'])->name('write-off');
        Route::get('/export', [DebtController::class, 'export'])->name('export');
    });

    // Clients
    Route::resource('clients', ClientController::class);
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::post('/{client}/contacts', [ClientController::class, 'addContact'])->name('add-contact');
        Route::get('/export', [ClientController::class, 'export'])->name('export');
    });

    // Finance (includes invoices, P&L, budget)
    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/export', [FinanceController::class, 'exportReport'])->name('export');
        Route::get('/budget', [FinanceController::class, 'budget'])->name('budget');
        Route::post('/budget', [FinanceController::class, 'storeBudget'])->name('store-budget');
    });

    // Zones (if not already defined)
    Route::resource('zones', ZoneController::class)->except(['create', 'edit']);

    // Invoices
    Route::resource('invoices', InvoiceController::class);

    // Collection Sessions
    Route::resource('collection-sessions', CollectionSessionController::class);

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
    });

    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Inertia fallback for unmatched routes (no auth required)
Route::fallback(function () {
    return Inertia::render('Errors/404');
});