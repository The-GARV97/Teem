<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use App\Services\LeaveBalanceService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveBalanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private LeaveBalanceService $service;
    private Organization $org;
    private LeaveType $leaveType;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->service  = new LeaveBalanceService();
        $this->org      = Organization::factory()->create();

        $user = User::factory()->create(['org_id' => $this->org->id, 'role' => 'member']);
        $dept  = Department::create(['org_id' => $this->org->id, 'name' => 'Test Dept']);
        $desig = Designation::create(['org_id' => $this->org->id, 'name' => 'Test Role']);

        $this->employee = Employee::create([
            'org_id'         => $this->org->id,
            'user_id'        => $user->id,
            'name'           => $user->name,
            'email'          => $user->email,
            'department_id'  => $dept->id,
            'designation_id' => $desig->id,
            'joining_date'   => now()->toDateString(),
            'status'         => 'active',
        ]);

        $this->leaveType = LeaveType::withoutGlobalScopes()->create([
            'org_id'   => $this->org->id,
            'name'     => 'Annual Leave',
            'max_days' => 20,
        ]);
    }

    public function test_get_or_init_creates_with_zero_used_days(): void
    {
        $balance = $this->service->getOrInit($this->org->id, $this->employee->id, $this->leaveType->id, 2024);

        $this->assertSame(0, $balance->used_days);
        $this->assertDatabaseHas('leave_balances', [
            'org_id'        => $this->org->id,
            'employee_id'   => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'year'          => 2024,
            'used_days'     => 0,
        ]);
    }

    public function test_increment_increases_used_days(): void
    {
        $this->service->getOrInit($this->org->id, $this->employee->id, $this->leaveType->id, 2024);
        $this->service->increment($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 3);

        $balance = LeaveBalance::withoutGlobalScopes()
            ->where('org_id', $this->org->id)
            ->where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->leaveType->id)
            ->where('year', 2024)
            ->first();

        $this->assertSame(3, $balance->used_days);
    }

    public function test_decrement_decreases_used_days(): void
    {
        $this->service->getOrInit($this->org->id, $this->employee->id, $this->leaveType->id, 2024);
        $this->service->increment($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 5);
        $this->service->decrement($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 2);

        $balance = LeaveBalance::withoutGlobalScopes()
            ->where('org_id', $this->org->id)
            ->where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->leaveType->id)
            ->where('year', 2024)
            ->first();

        $this->assertSame(3, $balance->used_days);
    }

    public function test_decrement_clamps_to_zero(): void
    {
        $this->service->getOrInit($this->org->id, $this->employee->id, $this->leaveType->id, 2024);
        $this->service->increment($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 2);
        $this->service->decrement($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 10);

        $balance = LeaveBalance::withoutGlobalScopes()
            ->where('org_id', $this->org->id)
            ->where('employee_id', $this->employee->id)
            ->where('leave_type_id', $this->leaveType->id)
            ->where('year', 2024)
            ->first();

        $this->assertSame(0, $balance->used_days);
    }

    public function test_has_sufficient_balance_returns_true_when_within_limit(): void
    {
        // max_days = 20, used = 0, requesting 5 → 5 <= 20
        $result = $this->service->hasSufficientBalance(
            $this->org->id, $this->employee->id, $this->leaveType, 2024, 5
        );

        $this->assertTrue($result);
    }

    public function test_has_sufficient_balance_returns_false_when_over_limit(): void
    {
        // max_days = 20, used = 18, requesting 5 → 23 > 20
        $this->service->increment($this->org->id, $this->employee->id, $this->leaveType->id, 2024, 18);

        $result = $this->service->hasSufficientBalance(
            $this->org->id, $this->employee->id, $this->leaveType, 2024, 5
        );

        $this->assertFalse($result);
    }
}
