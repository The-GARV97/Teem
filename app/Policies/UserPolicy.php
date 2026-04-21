<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function manageEmployees(User $authUser, User $targetUser): bool
    {
        return $authUser->hasPermissionTo('manage-employees') && $authUser->org_id === $targetUser->org_id;
    }

    public function create(User $authUser): bool
    {
        return $authUser->hasPermissionTo('manage-employees');
    }

    public function update(User $authUser, User $targetUser): bool
    {
        return $this->manageEmployees($authUser, $targetUser);
    }

    public function delete(User $authUser, User $targetUser): bool
    {
        return $this->manageEmployees($authUser, $targetUser);
    }
}
