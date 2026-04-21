<?php

namespace App\Policies;

use App\Models\User;

class LeavePolicy
{
    public function apply(User $user): bool
    {
        return $user->hasPermissionTo('apply-leave');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermissionTo('approve-leave');
    }
}
