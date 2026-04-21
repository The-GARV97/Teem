<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\LeaveRequestReviewed;
use App\Notifications\LeaveRequestSubmitted;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeAdmin(Organization $org): User
    {
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        return $user;
    }

    private function makeEmployeeUser(Organization $org): array
    {
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $dept  = Department::firstOrCreate(['org_id' => $org->id, 'name' => 'Test Dept']);
        $desig = Designation::firstOrCreate(['org_id' => $org->id, 'name' => 'Test Role']);

        $employee = Employee::create([
            'org_id'         => $org->id,
            'user_id'        => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        return [$user, $employee];
    }

    private function makeManagerUser(Organization $org): User
    {
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'manager']);
        $user->assignRole('Manager');

        return $user;
    }

    private function makeLeaveType(Organization $org, int $maxDays = 20): LeaveType
    {
        return LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Annual Leave',
            'max_days' => $maxDays,
        ]);
    }

    private function makePendingLeaveRequest(Organization $org, Employee $employee, LeaveType $leaveType): LeaveRequest
    {
        return LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'total_days'    => 3,
            'reason'        => 'Personal',
            'status'        => 'pending',
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_leave_application_notifies_managers_and_admins(): void
    {
        Notification::fake();

        $org     = Organization::factory()->create();
        $admin   = $this->makeAdmin($org);
        $manager = $this->makeManagerUser($org);
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'reason'        => 'Holiday',
        ])->assertRedirect(route('leave-requests.index'));

        // Both admin and manager should receive the notification
        Notification::assertSentTo($admin, LeaveRequestSubmitted::class);
        Notification::assertSentTo($manager, LeaveRequestSubmitted::class);

        // Employee should NOT receive the submitted notification
        Notification::assertNotSentTo($empUser, LeaveRequestSubmitted::class);
    }

    public function test_leave_approval_notifies_employee(): void
    {
        Notification::fake();

        $org   = Organization::factory()->create();
        $admin = $this->makeAdmin($org);
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType   = $this->makeLeaveType($org);
        $leaveRequest = $this->makePendingLeaveRequest($org, $employee, $leaveType);

        $this->actingAs($admin)
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertRedirect();

        Notification::assertSentTo($empUser, LeaveRequestReviewed::class, function ($notification) use ($empUser) {
            // Verify the notification carries 'approved' status by calling toDatabase with the real user
            $data = $notification->toDatabase($empUser);
            return $data['status'] === 'approved';
        });
    }

    public function test_leave_rejection_notifies_employee_with_reason(): void
    {
        Notification::fake();

        $org   = Organization::factory()->create();
        $admin = $this->makeAdmin($org);
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType    = $this->makeLeaveType($org);
        $leaveRequest = $this->makePendingLeaveRequest($org, $employee, $leaveType);

        $this->actingAs($admin)
            ->post(route('leave-requests.reject', $leaveRequest), [
                'rejection_reason' => 'Not enough staff available',
            ])
            ->assertRedirect();

        Notification::assertSentTo($empUser, LeaveRequestReviewed::class, function ($notification) use ($empUser) {
            $data = $notification->toDatabase($empUser);
            return $data['status'] === 'rejected'
                && $data['rejection_reason'] === 'Not enough staff available';
        });
    }

    public function test_notification_index_marks_all_as_read(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType    = $this->makeLeaveType($org);
        $leaveRequest = $this->makePendingLeaveRequest($org, $employee, $leaveType);

        // Manually send a database notification so we have something to read
        $empUser->notify(new LeaveRequestReviewed($leaveRequest->load('employee', 'leaveType'), 'approved'));

        // Confirm there is 1 unread notification before visiting the page
        $this->assertCount(1, $empUser->fresh()->unreadNotifications);

        // Visiting the notifications index marks all as read
        $response = $this->actingAs($empUser)->get(route('notifications.index'));
        $response->assertOk();

        // After the page load all notifications should be marked read
        $this->assertCount(0, $empUser->fresh()->unreadNotifications);
    }

    public function test_unauthenticated_cannot_access_notifications(): void
    {
        $response = $this->get(route('notifications.index'));

        $response->assertRedirect(route('login'));
    }
}
