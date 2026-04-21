<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function makeAdmin(): array
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        return [$org, $user];
    }

    private function makeEmployeeUser(Organization $org, string $role = 'member', string $spatieRole = 'Employee'): array
    {
        $user = User::factory()->create(['org_id' => $org->id, 'role' => $role]);
        $user->assignRole($spatieRole);
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

    private function makeLeaveType(Organization $org, int $maxDays = 20): LeaveType
    {
        return LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Annual Leave',
            'max_days' => $maxDays,
        ]);
    }

    public function test_employee_can_apply_for_leave(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $response = $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04', // Monday
            'end_date'      => '2024-03-06', // Wednesday
            'reason'        => 'Personal reasons',
        ]);

        $response->assertRedirect(route('leave-requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'org_id'      => $org->id,
            'employee_id' => $employee->id,
            'status'      => 'pending',
        ]);
    }

    public function test_insufficient_balance_prevents_leave_application(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org, 2); // only 2 days allowed

        $response = $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04', // Monday
            'end_date'      => '2024-03-08', // Friday = 5 days
            'reason'        => 'Vacation',
        ]);

        $response->assertSessionHasErrors('leave_type_id');
        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $employee->id]);
    }

    public function test_all_weekend_dates_prevented(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $response = $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-02', // Saturday
            'end_date'      => '2024-03-03', // Sunday
            'reason'        => 'Weekend trip',
        ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $employee->id]);
    }

    public function test_employee_sees_only_own_requests_in_index(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        [$otherUser, $otherEmployee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        // Create a request for the first employee
        LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-05',
            'total_days'    => 2,
            'reason'        => 'My leave',
            'status'        => 'pending',
        ]);

        // Create a request for the other employee
        LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $otherEmployee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-11',
            'end_date'      => '2024-03-12',
            'total_days'    => 2,
            'reason'        => 'Other leave',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($empUser)->get(route('leave-requests.index'));

        $response->assertOk();
        $requests = $response->viewData('requests');
        $this->assertCount(1, $requests);
        $this->assertEquals($employee->id, $requests->first()->employee_id);
    }

    public function test_admin_sees_all_org_requests_in_index(): void
    {
        [$org, $adminUser] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        [$empUser2, $employee2] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-05',
            'total_days'    => 2,
            'reason'        => 'Leave 1',
            'status'        => 'pending',
        ]);

        LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee2->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-11',
            'end_date'      => '2024-03-12',
            'total_days'    => 2,
            'reason'        => 'Leave 2',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($adminUser)->get(route('leave-requests.index'));

        $response->assertOk();
        $requests = $response->viewData('requests');
        $this->assertCount(2, $requests);
    }

    public function test_admin_can_approve_leave_request(): void
    {
        [$org, $adminUser] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $leaveRequest = LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'total_days'    => 3,
            'reason'        => 'Vacation',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('leave-requests.approve', $leaveRequest));

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id'     => $leaveRequest->id,
            'status' => 'approved',
        ]);

        // Balance should be incremented
        $this->assertDatabaseHas('leave_balances', [
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days'     => 3,
        ]);
    }

    public function test_admin_can_reject_leave_request(): void
    {
        [$org, $adminUser] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $leaveRequest = LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'total_days'    => 3,
            'reason'        => 'Vacation',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('leave-requests.reject', $leaveRequest), [
                'rejection_reason' => 'Not enough staff',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leave_requests', [
            'id'               => $leaveRequest->id,
            'status'           => 'rejected',
            'rejection_reason' => 'Not enough staff',
        ]);
    }

    public function test_cannot_approve_already_approved_request(): void
    {
        [$org, $adminUser] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $leaveRequest = LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'total_days'    => 3,
            'reason'        => 'Vacation',
            'status'        => 'approved',
        ]);

        $response = $this->actingAs($adminUser)
            ->post(route('leave-requests.approve', $leaveRequest));

        $response->assertRedirect();
        $response->assertSessionHasErrors('status');
    }

    public function test_non_approver_cannot_approve(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);
        $leaveType = $this->makeLeaveType($org);

        $leaveRequest = LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'total_days'    => 3,
            'reason'        => 'Vacation',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($empUser)
            ->post(route('leave-requests.approve', $leaveRequest));

        $response->assertRedirect('/dashboard');
    }
}
