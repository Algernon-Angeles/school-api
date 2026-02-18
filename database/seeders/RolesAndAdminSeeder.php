<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole   = Role::firstOrCreate(['name' => 'admin']);
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $facultyRole = Role::firstOrCreate(['name' => 'faculty']);

        // Create default admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('Admin1234'),
            ]
        );

        if (! $admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }
    }
}
