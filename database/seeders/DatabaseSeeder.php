<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AddressLabelSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ServiceSeeder::class,
            
            ArgentinaUsersSeeder::class,
            BusinessSeeder::class,
            PortugalUsersSeeder::class,
            PortugalBusinessSeeder::class,
        ]);
    }
}