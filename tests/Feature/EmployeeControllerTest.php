<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
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

    private function makeEmployee(int $orgId, Department $department, Designation $designation, array $overrides = []): Employee
    {
        static $counter = 0;
        $counter++;

        return Employee::create(array_merge([
            'org_id'         => $orgId,
            'name'           => "Employee {$counter}",
            'email'          => "employee{$counter}@example.com",
            'department_id'  => $department->id,
            'designation_id' => $designation->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ], $overrides));
    }

    public function test_admin_can_view_employee_directory(): void
    {
        [$org, $user] = $this->makeAdmin();

        $department  = Department::create(['org_id' => $org->id, 'name' => 'Engineering']);
        $designation = Designation::create(['org_id' => $org->id, 'name' => 'Dev']);
        $this->makeEmployee($org->id, $department, $designation);

        $response = $this->actingAs($user)->get(route('employees.index'));

        $response->assertOk();
        $response->assertSee('Employee 1');
    }

    public function test_directory_filters_by_department(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept1 = Department::create(['org_id' => $org->id, 'name' => 'HR']);
        $dept2 = Department::create(['org_id' => $org->id, 'name' => 'Finance']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Analyst']);

        $emp1 = $this->makeEmployee($org->id, $dept1, $desig, ['name' => 'Alice HR']);
        $emp2 = $this->makeEmployee($org->id, $dept2, $desig, ['name' => 'Bob Finance']);

        $response = $this->actingAs($user)
            ->get(route('employees.index', ['department_id' => $dept1->id]));

        $response->assertOk();
        $response->assertSee('Alice HR');
        $response->assertDontSee('Bob Finance');
    }

    public function test_directory_filters_by_status(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Ops']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Coordinator']);

        $active   = $this->makeEmployee($org->id, $dept, $desig, ['name' => 'Active Person', 'status' => 'active']);
        $inactive = $this->makeEmployee($org->id, $dept, $desig, ['name' => 'Inactive Person', 'status' => 'inactive']);

        $response = $this->actingAs($user)
            ->get(route('employees.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee('Active Person');
        $response->assertDontSee('Inactive Person');
    }

    public function test_directory_searches_by_name(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Marketing']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Specialist']);

        $this->makeEmployee($org->id, $dept, $desig, ['name' => 'Unique Searchable Name']);
        $this->makeEmployee($org->id, $dept, $desig, ['name' => 'Other Person']);

        $response = $this->actingAs($user)
            ->get(route('employees.index', ['search' => 'Unique Searchable']));

        $response->assertOk();
        $response->assertSee('Unique Searchable Name');
        $response->assertDontSee('Other Person');
    }

    public function test_admin_can_create_employee(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Product']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'PM']);

        $response = $this->actingAs($user)->post(route('employees.store'), [
            'name'           => 'New Employee',
            'email'          => 'newemployee@example.com',
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', ['email' => 'newemployee@example.com', 'org_id' => $org->id]);
    }

    public function test_admin_can_update_employee(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Design']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Designer']);
        $emp   = $this->makeEmployee($org->id, $dept, $desig, ['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('employees.update', $emp), [
            'name'           => 'Updated Name',
            'email'          => $emp->email,
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => $emp->joining_date->toDateString(),
            'status'         => 'active',
        ]);

        $response->assertRedirect(route('employees.show', $emp));
        $this->assertDatabaseHas('employees', ['id' => $emp->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_soft_delete_employee(): void
    {
        [$org, $user] = $this->makeAdmin();

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Support']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Agent']);
        $emp   = $this->makeEmployee($org->id, $dept, $desig, ['name' => 'To Be Deleted']);

        $response = $this->actingAs($user)->delete(route('employees.destroy', $emp));

        $response->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $emp->id]);

        // Deleted employee should not appear in index
        $indexResponse = $this->actingAs($user)->get(route('employees.index'));
        $indexResponse->assertDontSee('To Be Deleted');
    }

    public function test_non_admin_cannot_create_employee(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'IT']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Technician']);

        $response = $this->actingAs($user)->post(route('employees.store'), [
            'name'           => 'Unauthorized',
            'email'          => 'unauth@example.com',
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $response->assertStatus(403);
    }

    public function test_cross_org_employee_returns_404(): void
    {
        [$org, $user] = $this->makeAdmin();

        // Create employee in a different org
        $otherOrg  = Organization::factory()->create();
        $dept      = Department::create(['org_id' => $otherOrg->id, 'name' => 'Other Dept']);
        $desig     = Designation::create(['org_id' => $otherOrg->id, 'name' => 'Other Role']);
        $otherEmp  = Employee::withoutGlobalScopes()->create([
            'org_id'         => $otherOrg->id,
            'name'           => 'Other Org Employee',
            'email'          => 'other@example.com',
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('employees.show', $otherEmp->id));

        $response->assertNotFound();
    }
}
