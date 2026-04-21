<?php

namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveType;

class LeaveBalanceService
{
    public function getOrInit(int $orgId, int $employeeId, int $leaveTypeId, int $year): LeaveBalance
    {
        return LeaveBalance::withoutGlobalScopes()->firstOrCreate(
            [
                'org_id'         => $orgId,
                'employee_id'    => $employeeId,
                'leave_type_id'  => $leaveTypeId,
                'year'           => $year,
            ],
            ['used_days' => 0]
        );
    }

    public function increment(int $orgId, int $employeeId, int $leaveTypeId, int $year, int $days): void
    {
        $balance = $this->getOrInit($orgId, $employeeId, $leaveTypeId, $year);
        $balance->increment('used_days', $days);
    }

    public function decrement(int $orgId, int $employeeId, int $leaveTypeId, int $year, int $days): void
    {
        $balance = $this->getOrInit($orgId, $employeeId, $leaveTypeId, $year);
        $newValue = max(0, $balance->used_days - $days);
        $balance->update(['used_days' => $newValue]);
    }

    public function hasSufficientBalance(int $orgId, int $employeeId, LeaveType $leaveType, int $year, int $requestedDays): bool
    {
        $balance = $this->getOrInit($orgId, $employeeId, $leaveType->id, $year);
        return ($balance->used_days + $requestedDays) <= $leaveType->max_days;
    }
}
