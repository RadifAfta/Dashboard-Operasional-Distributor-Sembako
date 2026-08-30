<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions list grouped by module
        $permissions = [
            // Dashboard
            'view-dashboard',

            // Users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Roles & Permissions
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',

            // Settings
            'view-settings',
            'edit-settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // 1. Super Admin
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // 2. Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'view-dashboard',
            'view-users',
            'create-users',
            'edit-users',
            'view-roles',
            'view-settings',
            'edit-settings',
        ]);

        // 3. User
        $userRole = Role::firstOrCreate(['name' => 'User', 'guard_name' => 'web']);
        $userRole->syncPermissions([
            'view-dashboard',
        ]);
    }
}
