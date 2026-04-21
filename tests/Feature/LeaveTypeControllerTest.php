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

class LeaveTypeControllerTest extends TestCase
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

    public function test_admin_can_create_leave_type(): void
    {
        [$org, $user] = $this->makeAdmin();

        $response = $this->actingAs($user)
            ->post(route('leave-types.store'), ['name' => 'Sick Leave', 'max_days' => 10]);

        $response->assertRedirect(route('leave-types.index'));
        $this->assertDatabaseHas('leave_types', ['name' => 'Sick Leave', 'org_id' => $org->id]);
    }

    public function test_admin_can_update_leave_type(): void
    {
        [$org, $user] = $this->makeAdmin();

        $leaveType = LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Annual Leave',
            'max_days' => 15,
        ]);

        $response = $this->actingAs($user)
            ->put(route('leave-types.update', $leaveType), ['name' => 'Annual Leave', 'max_days' => 20]);

        $response->assertRedirect(route('leave-types.index'));
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id, 'max_days' => 20]);
    }

    public function test_admin_can_delete_leave_type_without_requests(): void
    {
        [$org, $user] = $this->makeAdmin();

        $leaveType = LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Casual Leave',
            'max_days' => 5,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect(route('leave-types.index'));
        $this->assertDatabaseMissing('leave_types', ['id' => $leaveType->id]);
    }

    public function test_cannot_delete_leave_type_with_requests(): void
    {
        [$org, $user] = $this->makeAdmin();
        [$empUser, $employee] = $this->makeEmployeeUser($org);

        $leaveType = LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Maternity Leave',
            'max_days' => 90,
        ]);

        LeaveRequest::withoutGlobalScopes()->create([
            'org_id'        => $org->id,
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date'    => '2024-03-01',
            'end_date'      => '2024-03-05',
            'total_days'    => 5,
            'reason'        => 'Medical',
            'status'        => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->delete(route('leave-types.destroy', $leaveType));

        $response->assertRedirect();
        $response->assertSessionHasErrors('leave_type');
        $this->assertDatabaseHas('leave_types', ['id' => $leaveType->id]);
    }

    public function test_non_admin_cannot_create_leave_type(): void
    {
        $org = Organization::factory()->create();
        [$empUser] = $this->makeEmployeeUser($org);

        $response = $this->actingAs($empUser)
            ->post(route('leave-types.store'), ['name' => 'Sick Leave', 'max_days' => 10]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseMissing('leave_types', ['name' => 'Sick Leave']);
    }

    public function test_duplicate_leave_type_name_in_same_org_fails(): void
    {
        [$org, $user] = $this->makeAdmin();

        LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $org->id,
            'name'     => 'Sick Leave',
            'max_days' => 10,
        ]);

        $response = $this->actingAs($user)
            ->post(route('leave-types.store'), ['name' => 'Sick Leave', 'max_days' => 5]);

        $response->assertSessionHasErrors('name');
    }
}
