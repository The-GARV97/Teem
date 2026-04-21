<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use App\Policies\EmployeePolicy;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    private function makeEmployee(int $orgId, Department $dept, Designation $desig, array $overrides = []): Employee
    {
        static $counter = 0;
        $counter++;

        return Employee::create(array_merge([
            'org_id'         => $orgId,
            'name'           => "Policy Employee {$counter}",
            'email'          => "policy{$counter}@example.com",
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ], $overrides));
    }

    public function test_admin_can_create_employee(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        $policy = new EmployeePolicy();

        $this->assertTrue($policy->create($user));
    }

    public function test_non_admin_cannot_create_employee(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $policy = new EmployeePolicy();

        $this->assertFalse($policy->create($user));
    }

    public function test_admin_can_update_own_org_employee(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Policy Dept']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Policy Role']);
        $emp   = $this->makeEmployee($org->id, $dept, $desig);

        $policy = new EmployeePolicy();

        $this->assertTrue($policy->update($user, $emp));
    }

    public function test_admin_cannot_update_cross_org_employee(): void
    {
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $org1->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        $dept  = Department::create(['org_id' => $org2->id, 'name' => 'Other Dept']);
        $desig = Designation::create(['org_id' => $org2->id, 'name' => 'Other Role']);
        $emp   = $this->makeEmployee($org2->id, $dept, $desig);

        $policy = new EmployeePolicy();

        $this->assertFalse($policy->update($user, $emp));
    }

    public function test_any_user_can_view_employees(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'View Dept']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'View Role']);
        $emp   = $this->makeEmployee($org->id, $dept, $desig);

        $policy = new EmployeePolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $emp));
    }
}
