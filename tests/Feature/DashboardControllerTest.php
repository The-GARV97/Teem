<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create(['org_id' => null, 'role' => 'superadmin']);
        $user->assignRole('SuperAdmin');

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

    // -------------------------------------------------------------------------
    // Admin dashboard
    // -------------------------------------------------------------------------

    public function test_admin_dashboard_loads_with_stats(): void
    {
        [$org, $admin] = $this->makeAdmin();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertArrayHasKey('active_employees', $stats);
        $this->assertArrayHasKey('total_departments', $stats);
        $this->assertArrayHasKey('pending_leaves', $stats);
        $this->assertArrayHasKey('approved_leaves', $stats);
        $this->assertArrayHasKey('rejected_leaves', $stats);
    }

    public function test_admin_dashboard_shows_correct_employee_count(): void
    {
        [$org, $admin] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Engineering']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Dev']);

        // Create 3 employees in this org
        foreach (range(1, 3) as $i) {
            Employee::create([
                'org_id'         => $org->id,
                'name'           => "Employee {$i}",
                'email'          => "emp{$i}@example.com",
                'department_id'  => $dept->id,
                'designation_id' => $desig->id,
                'joining_date'   => now()->toDateString(),
                'status'         => 'active',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(3, $stats['total_employees']);
        $this->assertEquals(3, $stats['active_employees']);
    }

    public function test_admin_dashboard_shows_correct_leave_counts(): void
    {
        [$org, $admin] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);

        $leaveType = LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Annual',
            'max_days' => 20,
        ]);

        // 2 pending, 1 approved, 1 rejected
        foreach (['pending', 'pending', 'approved', 'rejected'] as $status) {
            LeaveRequest::withoutGlobalScopes()->create([
                'org_id'        => $org->id,
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date'    => '2024-03-04',
                'end_date'      => '2024-03-05',
                'total_days'    => 2,
                'reason'        => 'Test',
                'status'        => $status,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['pending_leaves']);
        $this->assertEquals(1, $stats['approved_leaves']);
        $this->assertEquals(1, $stats['rejected_leaves']);
    }

    // -------------------------------------------------------------------------
    // SuperAdmin dashboard
    // -------------------------------------------------------------------------

    public function test_superadmin_dashboard_loads(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('total_organizations', $stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('total_employees', $stats);
        $this->assertArrayHasKey('pending_leaves', $stats);
        $this->assertArrayHasKey('approved_leaves', $stats);
    }

    public function test_superadmin_redirected_to_platform_dashboard_when_hitting_admin_route(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        // SuperAdmin has no org_id, so /dashboard redirects to superadmin view
        $response = $this->actingAs($superAdmin)->get(route('dashboard'));

        // The controller returns the superadmin view directly (not a redirect)
        $response->assertOk();
        $response->assertViewIs('superadmin.dashboard');
    }

    // -------------------------------------------------------------------------
    // Employee dashboard
    // -------------------------------------------------------------------------

    public function test_employee_dashboard_loads(): void
    {
        $org = Organization::factory()->create();
        [$empUser, $employee] = $this->makeEmployeeUser($org);

        $response = $this->actingAs($empUser)->get(route('employee.dashboard'));

        $response->assertOk();
        $response->assertViewIs('employee.dashboard');
    }

    // -------------------------------------------------------------------------
    // Manager dashboard
    // -------------------------------------------------------------------------

    public function test_manager_dashboard_loads(): void
    {
        $org     = Organization::factory()->create();
        $manager = $this->makeManagerUser($org);

        $response = $this->actingAs($manager)->get(route('manager.dashboard'));

        $response->assertOk();
        $response->assertViewIs('manager.dashboard');
    }

    // -------------------------------------------------------------------------
    // Unauthenticated
    // -------------------------------------------------------------------------

    public function test_unauthenticated_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
