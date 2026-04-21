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
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EndToEndWorkflowTest extends TestCase
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

    private function createOrg(string $name = 'Acme Corp'): Organization
    {
        return Organization::factory()->create(['name' => $name]);
    }

    private function createAdmin(Organization $org): User
    {
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        return $user;
    }

    private function createEmployeeUser(Organization $org, string $name = 'Test Employee'): array
    {
        $user = User::factory()->create([
            'org_id' => $org->id,
            'role'   => 'member',
            'name'   => $name,
        ]);
        $user->assignRole('Employee');

        $dept  = Department::firstOrCreate(['org_id' => $org->id, 'name' => 'General']);
        $desig = Designation::firstOrCreate(['org_id' => $org->id, 'name' => 'Staff']);

        $employee = Employee::create([
            'org_id'         => $org->id,
            'user_id'        => $user->id,
            'name'           => $name,
            'email'          => $user->email,
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        return [$user, $employee];
    }

    private function createLeaveType(Organization $org, string $name = 'Casual', int $maxDays = 10): LeaveType
    {
        return LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => $name,
            'max_days' => $maxDays,
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_full_leave_workflow(): void
    {
        // 1. Create org + admin + employee user + employee record
        $org   = $this->createOrg('WorkForge Inc');
        $admin = $this->createAdmin($org);
        [$empUser, $employee] = $this->createEmployeeUser($org, 'Jane Doe');

        // 2. Admin creates a leave type (Casual, 10 days)
        $this->actingAs($admin)->post(route('leave-types.store'), [
            'name'     => 'Casual',
            'max_days' => 10,
        ])->assertRedirect(route('leave-types.index'));

        $leaveType = LeaveType::withoutGlobalScopes()
            ->where('org_id', $org->id)
            ->where('name', 'Casual')
            ->firstOrFail();

        $this->assertEquals(10, $leaveType->max_days);

        // 3. Employee applies for leave (3 working days: Mon–Wed)
        $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04', // Monday
            'end_date'      => '2024-03-06', // Wednesday
            'reason'        => 'Personal reasons',
        ])->assertRedirect(route('leave-requests.index'));

        // 4. Assert leave request created with status=pending
        $leaveRequest = LeaveRequest::withoutGlobalScopes()
            ->where('org_id', $org->id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        $this->assertEquals('pending', $leaveRequest->status);
        $this->assertEquals(3, $leaveRequest->total_days);

        // 5. Admin approves the leave
        $this->actingAs($admin)
            ->post(route('leave-requests.approve', $leaveRequest))
            ->assertRedirect();

        // 6. Assert status=approved, balance incremented by 3
        $leaveRequest->refresh();
        $this->assertEquals('approved', $leaveRequest->status);

        $balance = LeaveBalance::withoutGlobalScopes()
            ->where('org_id', $org->id)
            ->where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->firstOrFail();

        $this->assertEquals(3, $balance->used_days);

        // 7. Assert employee user received a notification
        $this->assertCount(1, $empUser->fresh()->notifications);
        $notification = $empUser->fresh()->notifications->first();
        $this->assertEquals('approved', $notification->data['status']);

        // 8. Assert admin dashboard pending count decreased (now 0)
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['pending_leaves']);
    }

    public function test_employee_cannot_exceed_leave_balance(): void
    {
        // 1. Create org + admin + employee with leave type (max 2 days)
        $org   = $this->createOrg('SmallCo');
        $admin = $this->createAdmin($org);
        [$empUser, $employee] = $this->createEmployeeUser($org, 'Bob Smith');
        $leaveType = $this->createLeaveType($org, 'Sick', 2);

        // 2. Employee applies for 5 days → assert rejected with error
        $response = $this->actingAs($empUser)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-04', // Monday
            'end_date'      => '2024-03-08', // Friday = 5 working days
            'reason'        => 'Vacation',
        ]);

        $response->assertSessionHasErrors('leave_type_id');

        // 3. Assert no leave request created
        $this->assertDatabaseMissing('leave_requests', ['employee_id' => $employee->id]);
    }

    public function test_org_isolation_full_scenario(): void
    {
        // 1. Create two orgs (A and B) each with admin + employees
        $orgA   = $this->createOrg('Org Alpha');
        $adminA = $this->createAdmin($orgA);
        [$empUserA, $employeeA] = $this->createEmployeeUser($orgA, 'Alice A');

        $orgB   = $this->createOrg('Org Beta');
        $adminB = $this->createAdmin($orgB);
        [$empUserB, $employeeB] = $this->createEmployeeUser($orgB, 'Bob B');

        // 2. Org A admin creates a department
        $this->actingAs($adminA)->post(route('departments.store'), [
            'name' => 'Alpha Engineering',
        ])->assertRedirect(route('departments.index'));

        $deptA = Department::withoutGlobalScopes()
            ->where('org_id', $orgA->id)
            ->where('name', 'Alpha Engineering')
            ->firstOrFail();

        // 3. Org B admin cannot see org A's department
        $responseB = $this->actingAs($adminB)->get(route('departments.index'));
        $responseB->assertOk();
        $responseB->assertDontSee('Alpha Engineering');

        // 4. Org A employee applies for leave
        $leaveTypeA = $this->createLeaveType($orgA, 'Annual', 15);

        $this->actingAs($empUserA)->post(route('leave-requests.store'), [
            'leave_type_id' => $leaveTypeA->id,
            'start_date'    => '2024-03-04',
            'end_date'      => '2024-03-06',
            'reason'        => 'Holiday',
        ])->assertRedirect(route('leave-requests.index'));

        $leaveRequestA = LeaveRequest::withoutGlobalScopes()
            ->where('org_id', $orgA->id)
            ->where('employee_id', $employeeA->id)
            ->firstOrFail();

        // 5. Org B admin cannot see org A's leave requests
        // The OrgScope filters by the authenticated user's org_id
        $leavesForB = LeaveRequest::withoutGlobalScopes()
            ->where('org_id', $orgB->id)
            ->get();

        $this->assertCount(0, $leavesForB);

        // Confirm org A's request is scoped to org A only
        $this->assertEquals($orgA->id, $leaveRequestA->org_id);
        $this->assertNotEquals($orgB->id, $leaveRequestA->org_id);
    }

    public function test_registration_creates_org_and_admin_can_access_dashboard(): void
    {
        // 1. POST /register with company_name, name, email, password
        $response = $this->post(route('register'), [
            'company_name'          => 'NewCo Ltd',
            'name'                  => 'John Founder',
            'email'                 => 'john@newco.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // 2. Assert org created, user created with role=admin
        $this->assertDatabaseHas('organizations', ['name' => 'NewCo Ltd']);
        $this->assertDatabaseHas('users', [
            'email' => 'john@newco.com',
            'role'  => 'admin',
        ]);

        $user = User::where('email', 'john@newco.com')->firstOrFail();
        $org  = Organization::where('name', 'NewCo Ltd')->firstOrFail();
        $this->assertEquals($org->id, $user->org_id);

        // 3. Assert redirected to /dashboard
        $response->assertRedirect(route('dashboard'));

        // 4. Assert dashboard returns 200
        // Mark email as verified (registration fires Registered event but doesn't auto-verify)
        $user->markEmailAsVerified();
        // Assign Admin role so the role middleware passes
        $user->assignRole('Admin');

        $dashResponse = $this->actingAs($user)->get(route('dashboard'));
        $dashResponse->assertOk();
    }
}
