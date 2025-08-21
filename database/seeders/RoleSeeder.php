<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'api';
        $permissions = Permission::where('guard_name', $guardName)->get();

        // SUPER ADMIN ROLE
        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => $guardName]);
        $superRole->syncPermissions($permissions);

        // MANAGER ROLE
        $managerRole = Role::create(['name' => 'Manager', 'guard_name' => $guardName]);
        $managerRole->syncPermissions($permissions->whereIn('name', ['Manager', 'Admin', 'Employee', 'Customer']));

        // ADMIN ROLE
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => $guardName]);
        $adminRole->syncPermissions($permissions->whereIn('name', ['Admin', 'Employee', 'Customer']));

        // EMPLOYEE ROLE
        $employeeRole = Role::create(['name' => 'Employee', 'guard_name' => $guardName]);
        $employeeRole->syncPermissions($permissions->whereIn('name', ['Employee', 'Customer']));

        // CUSTOMER ROLE
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => $guardName]);
        $customerRole->syncPermissions($permissions->whereIn('name', ['Customer']));
    }
}