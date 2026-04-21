<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('SUPERADMIN_EMAIL', 'superadmin@workforge.com')],
            [
                'name'     => 'Super Admin',
                'email'    => env('SUPERADMIN_EMAIL', 'superadmin@workforge.com'),
                'password' => Hash::make(env('SUPERADMIN_PASSWORD', 'password')),
                'role'     => 'superadmin',
                'org_id'   => null,
            ]
        );
    }
}
