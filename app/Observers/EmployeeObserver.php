<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\Scopes\OrgScope;

class EmployeeObserver
{
    public function deleting(Employee $employee): void
    {
        Employee::withoutGlobalScope(OrgScope::class)
            ->where('manager_id', $employee->id)
            ->update(['manager_id' => null]);
    }
}
