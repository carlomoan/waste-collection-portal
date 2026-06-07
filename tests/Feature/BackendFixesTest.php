<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\LeaveRequest;
use App\Models\ScheduledReport;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendFixesTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        return $user;
    }

    public function test_attendance_index_loads_without_redirect_loop(): void
    {
        $this->actingUser();

        // Regression: leave_requests table was missing, causing the index to
        // throw and redirect back to itself (302 loop).
        $this->get('/attendance')->assertOk();
    }

    public function test_audit_export_is_not_shadowed_by_show_route(): void
    {
        $this->actingUser();

        // Regression: /audit/export was matched by /audit/{auditLog}, producing
        // an invalid-bigint (22P02) error on Postgres.
        $response = $this->get('/audit/export');
        $response->assertOk();
        $this->assertStringContainsString('audit-logs.csv', $response->headers->get('content-disposition') ?? '');
    }

    public function test_browser_logs_endpoint_accepts_payload(): void
    {
        $this->postJson('/_boost/browser-logs', [
            'logs' => [
                ['level' => 'error', 'message' => 'Test client error', 'url' => '/dashboard'],
            ],
        ])->assertOk()->assertJson(['status' => 'ok']);
    }

    public function test_budget_supports_soft_deletes(): void
    {
        $budget = Budget::create([
            'name' => 'Q1 Budget',
            'total_budget' => 1000,
            'year' => 2026,
        ]);

        $budget->delete();

        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
        $this->assertSame(0, Budget::count());
        $this->assertSame(1, Budget::withTrashed()->count());
    }

    public function test_scheduled_report_supports_soft_deletes(): void
    {
        $report = ScheduledReport::create([
            'name' => 'Weekly Finance',
            'type' => 'financial',
            'frequency' => 'weekly',
        ]);

        $report->delete();

        $this->assertSoftDeleted('scheduled_reports', ['id' => $report->id]);
    }

    public function test_leave_request_persists_leave_type_and_days(): void
    {
        $user = User::factory()->create();
        $staff = Staff::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => '255700000111',
            'role' => 'collector',
            'hire_date' => '2026-01-01',
            'is_active' => true,
        ]);

        $leave = LeaveRequest::create([
            'staff_id' => $staff->id,
            'leave_type' => 'sick',
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-12',
            'days' => 3,
            'reason' => 'Flu',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'leave_type' => 'sick',
            'days' => 3,
        ]);
    }

    public function test_audit_show_route_only_matches_numeric_ids(): void
    {
        $this->actingUser();

        $log = AuditLog::create([
            'action' => 'created',
            'module' => 'User',
            'description' => 'test',
            'timestamp' => now(),
        ]);

        $this->get('/audit/'.$log->id)->assertOk();
    }
}
