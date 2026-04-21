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

class DepartmentControllerTest extends TestCase
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

    public function test_admin_can_create_department(): void
    {
        [$org, $user] = $this->makeAdmin();

        $response = $this->actingAs($user)
            ->post(route('departments.store'), ['name' => 'Engineering']);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['name' => 'Engineering', 'org_id' => $org->id]);
    }

    public function test_admin_can_update_department(): void
    {
        [$org, $user] = $this->makeAdmin();

        $department = Department::create(['org_id' => $org->id, 'name' => 'HR']);

        $response = $this->actingAs($user)
            ->put(route('departments.update', $department), ['name' => 'Human Resources']);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Human Resources']);
    }

    public function test_admin_can_delete_department_without_employees(): void
    {
        [$org, $user] = $this->makeAdmin();

        $department = Department::create(['org_id' => $org->id, 'name' => 'Finance']);

        $response = $this->actingAs($user)
            ->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_cannot_delete_department_with_assigned_employees(): void
    {
        [$org, $user] = $this->makeAdmin();

        $department  = Department::create(['org_id' => $org->id, 'name' => 'Sales']);
        $designation = Designation::create(['org_id' => $org->id, 'name' => 'Rep']);

        Employee::create([
            'org_id'         => $org->id,
            'name'           => 'John Doe',
            'email'          => 'john@example.com',
            'department_id'  => $department->id,
            'designation_id' => $designation->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('departments.destroy', $department));

        $response->assertRedirect();
        $response->assertSessionHasErrors('department');
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }

    public function test_non_admin_cannot_create_department(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $response = $this->actingAs($user)
            ->post(route('departments.store'), ['name' => 'Marketing']);

        $response->assertStatus(403);
    }

    public function test_duplicate_department_name_in_same_org_fails_validation(): void
    {
        [$org, $user] = $this->makeAdmin();

        Department::create(['org_id' => $org->id, 'name' => 'IT']);

        $response = $this->actingAs($user)
            ->post(route('departments.store'), ['name' => 'IT']);

        $response->assertSessionHasErrors('name');
    }
}
