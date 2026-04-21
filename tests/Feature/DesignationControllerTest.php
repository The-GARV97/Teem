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

class DesignationControllerTest extends TestCase
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

    public function test_admin_can_create_designation(): void
    {
        [$org, $user] = $this->makeAdmin();

        $response = $this->actingAs($user)
            ->post(route('designations.store'), ['name' => 'Software Engineer']);

        $response->assertRedirect(route('designations.index'));
        $this->assertDatabaseHas('designations', ['name' => 'Software Engineer', 'org_id' => $org->id]);
    }

    public function test_admin_can_update_designation(): void
    {
        [$org, $user] = $this->makeAdmin();

        $designation = Designation::create(['org_id' => $org->id, 'name' => 'Junior Dev']);

        $response = $this->actingAs($user)
            ->put(route('designations.update', $designation), ['name' => 'Senior Dev']);

        $response->assertRedirect(route('designations.index'));
        $this->assertDatabaseHas('designations', ['id' => $designation->id, 'name' => 'Senior Dev']);
    }

    public function test_admin_can_delete_designation_without_employees(): void
    {
        [$org, $user] = $this->makeAdmin();

        $designation = Designation::create(['org_id' => $org->id, 'name' => 'Intern']);

        $response = $this->actingAs($user)
            ->delete(route('designations.destroy', $designation));

        $response->assertRedirect(route('designations.index'));
        $this->assertDatabaseMissing('designations', ['id' => $designation->id]);
    }

    public function test_cannot_delete_designation_with_assigned_employees(): void
    {
        [$org, $user] = $this->makeAdmin();

        $department  = Department::create(['org_id' => $org->id, 'name' => 'Engineering']);
        $designation = Designation::create(['org_id' => $org->id, 'name' => 'Lead']);

        Employee::create([
            'org_id'         => $org->id,
            'name'           => 'Jane Doe',
            'email'          => 'jane@example.com',
            'department_id'  => $department->id,
            'designation_id' => $designation->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('designations.destroy', $designation));

        $response->assertRedirect();
        $response->assertSessionHasErrors('designation');
        $this->assertDatabaseHas('designations', ['id' => $designation->id]);
    }

    public function test_non_admin_cannot_create_designation(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'member']);
        $user->assignRole('Employee');

        $response = $this->actingAs($user)
            ->post(route('designations.store'), ['name' => 'Analyst']);

        $response->assertStatus(403);
    }

    public function test_duplicate_designation_name_in_same_org_fails_validation(): void
    {
        [$org, $user] = $this->makeAdmin();

        Designation::create(['org_id' => $org->id, 'name' => 'Manager']);

        $response = $this->actingAs($user)
            ->post(route('designations.store'), ['name' => 'Manager']);

        $response->assertSessionHasErrors('name');
    }
}
