<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Ramsey\Uuid\Uuid;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primero los permisos y roles
        $guardName = 'api';
        $permissions = [
            Permission::create(['name' => 'Super Admin', 'guard_name' => $guardName]),
            Permission::create(['name' => 'Manager', 'guard_name' => $guardName]),
            Permission::create(['name' => 'Admin', 'guard_name' => $guardName]),
            Permission::create(['name' => 'Employee', 'guard_name' => $guardName]),
            Permission::create(['name' => 'Customer', 'guard_name' => $guardName]),
        ];

        // Crear roles y asignar permisos
        // SUPER ADMIN USER
        $superRole = Role::create(['name' => 'Super Admin', 'guard_name' => $guardName]);
        $superRole->syncPermissions($permissions);

        $superUser = User::factory()->create([
            'name' => 'Super Admin',
            'username' => 'superadmin369',
            'email' => 'superadmin@example.com',
            'uuid' => Uuid::uuid4()->toString(25),
            'phone' => '00000',
            'password' => bcrypt('Sa98765=')
        ]);
        $superUser->assignRole($superRole);
        // END SUPER ADMIN USER

        // MANAGER USER
        $managerRole = Role::create(['name' => 'Manager', 'guard_name' => $guardName]);
        $managerRole->syncPermissions([$permissions[1], $permissions[2], $permissions[3], $permissions[4]]); // Manager tiene todos los permisos

        $managerUser = User::factory()->create([
            'name' => 'Manager',
            'username' => 'manager369',
             'uuid' => Uuid::uuid4()->toString(25),
            'email' => 'manager@manager.com',
            'phone' => '00000',
            'password' => bcrypt('Fy98765=')
        ]);
        $managerUser->assignRole($managerRole);
        // END MANAGER USER

        // ADMIN USER
        $adminRole = Role::create(['name' => 'Admin', 'guard_name' => $guardName]);
        $adminRole->syncPermissions([$permissions[2], $permissions[3], $permissions[4]]); // Admin tiene permisos de Customer y Employee

        $adminUser = User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin369',
            'email' => 'admin@admin.com',
            'uuid' => Uuid::uuid4()->toString(),
            'phone' => '00000',
            'password' => bcrypt('Fy98765=')
        ]);
        $adminUser->assignRole($adminRole);
        // END ADMIN USER

        // EMPLOYEE USER
        $employeeRole = Role::create(['name' => 'Employee', 'guard_name' => $guardName]);
        $employeeRole->syncPermissions([$permissions[3],$permissions[4]]);

        $employeeUser = User::factory()->create([
            'name' => 'Employee',
            'username' => 'employee369',
            'email' => 'employee@employee.com',
            'uuid' => Uuid::uuid4()->toString(),
            'phone' => '00000',
            'password' => bcrypt('Fy98765=')
        ]);
        $employeeUser->assignRole($employeeRole);
        // END EMPLOYEE USER

        // CUSTOMER USER
        $customerRole = Role::create(['name' => 'Customer', 'guard_name' => $guardName]);
        $customerRole->syncPermissions([$permissions[4]]);

        $customerUser = User::factory()->create([
            'name' => 'Customer',
            'username' => 'customer369',
            'email' => 'customer@customer.com',
            'uuid' => Uuid::uuid4()->toString(),
            'phone' => '00000',
            'password' => bcrypt('Fy98765=')
        ]);
        $customerUser->assignRole($customerRole);
        // END CUSTOMER USER

        // Luego las categorías y servicios
        // CATEGORIAS
        $categories = [
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Internacional', 'category_description' => 'Descripción de Cocina Internacional', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/oMAAZvmiXpRoIXhPMUgan7o4m6GS0VngGzuExxF4.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Comida Rápida', 'category_description' => 'Descripción de Comida Rápida', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/3R7zsx7VXzFekjM2qFz0pktxpYeMnhT6b7fKmTo5.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pizzerías', 'category_description' => 'Descripción de Pizzerías', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/zy66xbwjltRgitojWuzbUoOxX4OpLfQUmlotimVi.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Japonesa', 'category_description' => 'Descripción de Cocina Japonesa', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/rEvbOnKbTYnpoz7Qc1Vu0jHXSDt8YMer2WaxqyOz.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Carnes y Parrillas', 'category_description' => 'Descripción de Carnes y Parrillas', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/DXj6PxsVPe9inTs8eDh2u47b1LhgwOpOXyYNV7Gp.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Fusión', 'category_description' => 'Descripción de Fusión', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/6uT3gxrcugQPiMIxICRVG06cHQbYBOCnPKolRqIh.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Vegetariana', 'category_description' => 'Descripción de Cocina Vegetariana', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/BpIS39yEA6lpcIRjSbkWC3Tt4lgrp2Pk4NclnMKn.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Mexicana', 'category_description' => 'Descripción de Cocina Mexicana', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/6rvuMCOXDbQm08Xf5D2snlnBj5sfTIcMF5PeLtcB.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Koreana', 'category_description' => 'Descripción de Cocina Koreana', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/FoJ3Uycii6zgFHelMfU6rR5w0A0PHnjcKv8jvMuy.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cocina Portuguesa', 'category_description' => 'Descripción de Cocina Portuguesa', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/OKZVzDcXq0csGYOsBZiSPa16oMrovNzQBr8sYmAT.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pastelería y Postres', 'category_description' => 'Descripción de Pastelería y Postres', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/eo6lgLdLH87PCLbwjyNVTTuNeXzxW4Jx67EGNDaS.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Pubs y Vinerías', 'category_description' => 'Descripción de Pubs y Vinerías', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/31DbV0wNR6topTeaVpBBlD8AdtJrSxD26XgC16hQ.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Cafés y Desayunos', 'category_description' => 'Descripción de Cafés y Desayunos', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/NC2D2vwB28ReT6E9HyBaq1rkJJLGCBodBLdNLvee.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Mercados y Tiendas', 'category_description' => 'Descripción de Mercados y Tiendas', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/9UrhIE91n2kbClChH8d0zAJ3WHdKv4cQ06fqq2NU.jpg', 'user_id' => 1],
            ['category_uuid' => Uuid::uuid4()->toString(), 'category_name' => 'Escuelas de Cocina', 'category_description' => 'Descripción de Escuelas de Cocina', 'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/sck0rO2HlaBnXzHMAaYPLuIJhYkTlqmMauOrZZYM.jpg', 'user_id' => 1]
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
        // END CATEGORIAS

        // SERVICES
        $services = [
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Wifi', 'service_description' => 'Descripción de Wifi', 'service_image_path' => 'storage/app/public/services_images/wifi.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Multilenguage', 'service_description' => 'Descripción de Multilenguage', 'service_image_path' => 'storage/app/public/services_images/multilenguage.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Kid Chairs', 'service_description' => 'Descripción de Kid Chairs', 'service_image_path' => 'storage/app/public/services_images/kid_chairs.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Baby Changing St..', 'service_description' => 'Descripción de Baby Changing St..', 'service_image_path' => 'storage/app/public/services_images/baby_changing_st.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Outdoor', 'service_description' => 'Outdoor Seating', 'service_image_path' => 'storage/app/public/services_images/outdoor.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'PMR', 'service_description' => 'Descripción de PMR', 'service_image_path' => 'storage/app/public/services_images/pmr.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Kids Play Area', 'service_description' => 'Descripción de Kids Play Area', 'service_image_path' => 'storage/app/public/services_images/kid_play.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Delivery', 'service_description' => 'Descripción de Delivery', 'service_image_path' => 'storage/app/public/services_images/delivery.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Take Away', 'service_description' => 'Descripción de Take Away', 'service_image_path' => 'storage/app/public/services_images/take_away.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Smoking Area', 'service_description' => 'Descripción de Smoking Area', 'service_image_path' => 'storage/app/public/services_images/smoking_area.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Happy Hours', 'service_description' => 'Descripción de Happy Hours', 'service_image_path' => 'storage/app/public/services_images/happy_hours.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Happy Birthday', 'service_description' => 'Descripción de Happy Birthday', 'service_image_path' => 'storage/app/public/services_images/happy_birthday.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Parking', 'service_description' => 'Descripción de Parking', 'service_image_path' => 'storage/app/public/services_images/parking.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Pet Friendly', 'service_description' => 'Descripción de Pet Friendly', 'service_image_path' => 'storage/app/public/services_images/pet_friendly.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Catering', 'service_description' => 'Descripción de Catering', 'service_image_path' => 'storage/app/public/services_images/catering.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Live Music', 'service_description' => 'Descripción de Live Music', 'service_image_path' => 'storage/app/public/services_images/livemusic.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'On Site', 'service_description' => 'Descripción de On Site', 'service_image_path' => 'storage/app/public/services_images/on_site.png', 'user_id' => 1],
            ['service_uuid' => Uuid::uuid4()->toString(), 'service_name' => 'Kids Menu', 'service_description' => 'Descripción de Kids Menu', 'service_image_path' => 'storage/app/public/services_images/kids_menu.png', 'user_id' => 1],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
        // END CATEGORIAS

        // Finalmente los usuarios argentinos
        $this->call([
            AddressLabelSeeder::class,
            ArgentinaUsersSeeder::class,
            BusinessSeeder::class, 
        ]);
    }
}