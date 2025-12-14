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
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions and roles
        $definedPermissions = [
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        $definedRoles = [
            'superadmin',
            'admin',
            'user',
        ];

        // Create permissions with api guard (only if they don't exist)
        foreach ($definedPermissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api']
            );
        }

        // In non-production environments, remove permissions not in the defined list
        if (!app()->isProduction()) {
            Permission::where('guard_name', 'api')
                ->whereNotIn('name', $definedPermissions)
                ->delete();
        }

        // Create roles with api guard (only if they don't exist)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'api']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // In non-production environments, remove roles not in the defined list
        if (!app()->isProduction()) {
            Role::where('guard_name', 'api')
                ->whereNotIn('name', $definedRoles)
                ->delete();
        }

        // Assign all permissions to superadmin (sync to avoid duplicates)
        $superadmin->syncPermissions(Permission::all());
    }
}
