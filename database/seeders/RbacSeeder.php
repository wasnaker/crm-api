<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('spine.rbac.guard') ?: config('permission.defaults.guard', 'sanctum');

        // Create permissions
        $permissions = [
            'users:view',
            'users:manage',
            'roles:view',
            'roles:manage',
            'permissions:view',
            'permissions:manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => $guard],
                ['guard_name' => $guard]
            );
        }

        // Assign all permissions to admin role
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => $guard],
            ['guard_name' => $guard]
        );
        $adminRole->syncPermissions($permissions);

        $this->command->info('RBAC permissions seeded.');
    }
}