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

class EmployeeObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_soft_deleting_manager_nulls_subordinate_manager_id(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['org_id' => $org->id, 'role' => 'admin']);
        $user->assignRole('Admin');

        $dept  = Department::create(['org_id' => $org->id, 'name' => 'Engineering']);
        $desig = Designation::create(['org_id' => $org->id, 'name' => 'Engineer']);

        $manager = Employee::create([
            'org_id'         => $org->id,
            'name'           => 'Manager Person',
            'email'          => 'manager@example.com',
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $subordinate = Employee::create([
            'org_id'         => $org->id,
            'name'           => 'Subordinate Person',
            'email'          => 'subordinate@example.com',
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'manager_id'     => $manager->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $this->assertEquals($manager->id, $subordinate->manager_id);

        $this->actingAs($user)->delete(route('employees.destroy', $manager));

        $this->assertNull($subordinate->fresh()->manager_id);
    }
}
