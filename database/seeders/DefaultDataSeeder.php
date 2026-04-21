<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Designation;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create a demo organization
        $org = Organization::firstOrCreate(
            ['name' => 'WorkForge Demo'],
        );

        // Create a demo admin user for this org
        $admin = User::firstOrCreate(
            ['email' => env('DEMO_ADMIN_EMAIL', 'admin@workforge.com')],
            [
                'name'     => 'Demo Admin',
                'password' => Hash::make(env('DEMO_ADMIN_PASSWORD', 'password')),
                'role'     => 'admin',
                'org_id'   => $org->id,
            ]
        );

        if (!$admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        // Seed default departments
        $departments = [
            'Engineering',
            'Human Resources',
            'Finance',
            'Marketing',
            'Sales',
            'Operations',
            'Product',
            'Design',
            'Legal',
            'Customer Support',
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(
                ['org_id' => $org->id, 'name' => $name]
            );
        }

        // Seed default designations
        $designations = [
            'Software Engineer',
            'Senior Software Engineer',
            'Tech Lead',
            'Engineering Manager',
            'Product Manager',
            'UI/UX Designer',
            'HR Manager',
            'HR Executive',
            'Finance Manager',
            'Accountant',
            'Marketing Manager',
            'Marketing Executive',
            'Sales Manager',
            'Sales Executive',
            'Operations Manager',
            'Business Analyst',
            'QA Engineer',
            'DevOps Engineer',
            'Customer Support Executive',
            'Team Lead',
        ];

        foreach ($designations as $name) {
            Designation::firstOrCreate(
                ['org_id' => $org->id, 'name' => $name]
            );
        }

        $this->command->info('Default data seeded: 1 org, 1 admin, ' . count($departments) . ' departments, ' . count($designations) . ' designations.');

        // Seed default leave types
        $leaveTypes = [
            ['name' => 'Casual Leave', 'max_days' => 12],
            ['name' => 'Sick Leave',   'max_days' => 10],
            ['name' => 'Paid Leave',   'max_days' => 15],
        ];

        foreach ($leaveTypes as $lt) {
            LeaveType::firstOrCreate(
                ['org_id' => $org->id, 'name' => $lt['name']],
                ['max_days' => $lt['max_days']]
            );
        }

        $this->command->info('Default data seeded: 1 org, 1 admin, ' . count($departments) . ' departments, ' . count($designations) . ' designations, ' . count($leaveTypes) . ' leave types.');
    }
}