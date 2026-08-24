<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CollectionSession;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CollectionSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = CollectionSession::query()
            ->with(['staff.user', 'payments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('staff_id'), fn ($q) => $q->where('staff_id', $request->staff_id))
            ->orderBy('session_date', 'desc')
            ->paginate(30);

        return Inertia::render('CollectionSessions/Index', [
            'sessions' => $sessions,
            'staff' => Staff::query()->with('user')->where('role', 'collector')->get(),
        ]);
    }

    public function show(CollectionSession $collectionSession): Response
    {
        $collectionSession->load(['staff.user', 'payments.client']);

        return Inertia::render('CollectionSessions/Show', ['session' => $collectionSession]);
    }

    public function update(Request $request, CollectionSession $collectionSession): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,submitted,completed,verified',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $collectionSession->update(['status' => $request->status]);
        AuditLog::log('session.update', 'CollectionSession', $collectionSession->id, ['status' => $request->status]);

        return back()->with('success', 'Session status updated.');
    }

    public function destroy(CollectionSession $collectionSession): RedirectResponse
    {
        if ($collectionSession->payments()->exists()) {
            return back()->with('error', 'Cannot delete session with associated payments.');
        }

        $collectionSession->delete();

        return redirect()->route('collection-sessions.index')->with('success', 'Session deleted.');
    }
}
