<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $manageEmployees = Permission::firstOrCreate(['name' => 'manage-employees']);
        $approveLeave    = Permission::firstOrCreate(['name' => 'approve-leave']);
        $applyLeave      = Permission::firstOrCreate(['name' => 'apply-leave']);

        // Create roles and assign permissions
        $superAdmin = Role::firstOrCreate(['name' => 'SuperAdmin']);
        $admin      = Role::firstOrCreate(['name' => 'Admin']);
        $manager    = Role::firstOrCreate(['name' => 'Manager']);
        $employee   = Role::firstOrCreate(['name' => 'Employee']);

        $superAdmin->syncPermissions(Permission::all());
        $admin->syncPermissions([$manageEmployees, $approveLeave]);
        $manager->syncPermissions([$approveLeave]);
        $employee->syncPermissions([$applyLeave]);

        $this->syncExistingUsers();
    }

    private function syncExistingUsers(): void
    {
        $map = [
            'superadmin' => 'SuperAdmin',
            'admin'      => 'Admin',
            'manager'    => 'Manager',
            'member'     => 'Employee',
        ];

        User::all()->each(function (User $user) use ($map) {
            $spatieName = $map[$user->role] ?? null;
            if ($spatieName === null) {
                Log::warning("RoleAndPermissionSeeder: unknown role '{$user->role}' for user {$user->id}, skipping.");
                return;
            }
            if (!$user->hasRole($spatieName)) {
                $user->assignRole($spatieName);
            }
        });
    }
}
