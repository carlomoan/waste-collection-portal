<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Staff;
use App\Models\CollectionSchedule;
use App\Services\ReportService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard', [
            'stats' => app(ReportService::class)->monthlyCompany(now()->month, now()->year),
            'recentTransactions' => Payment::latest('paid_at')->limit(6)->with('client', 'staff')->get(),
            'collectors' => Staff::where('role', 'collector')->with('user')->get(),
            'weekSchedule' => CollectionSchedule::where('is_active', true)
                ->with('zone', 'staff')
                ->where('effective_from', '<=', now())
                ->where(function ($query) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', now());
                })
                ->get(),
        ]);
    }
}
