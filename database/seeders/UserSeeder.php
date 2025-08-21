<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\AddressLabel;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Ramsey\Uuid\Uuid;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guardName = 'api';
        
        // Obtener labels de direcciones disponibles
        $labels = AddressLabel::pluck('id')->toArray();
        
        // Si no hay labels, crear uno por defecto
        if (empty($labels)) {
            $defaultLabel = AddressLabel::create([
                'name' => 'Casa',
                'description' => 'Dirección de casa'
            ]);
            $labels = [$defaultLabel->id];
        }

        $users = [
            [
                'name' => 'Super Admin',
                'username' => 'superadmin369',
                'email' => 'superadmin@example.com',
                'phone' => '+351 912 345 678',
                'password' => 'Sa98765=',
                'role' => 'Super Admin',
                'address' => 'Rua da Liberdade, 123, 1º Andar',
                'city' => 'Lisboa, Lisboa',
                'zip_code' => '1250-146',
                'latitude' => 38.7223,
                'longitude' => -9.1393
            ],
            [
                'name' => 'Manager',
                'username' => 'manager369',
                'email' => 'manager@manager.com',
                'phone' => '+351 913 456 789',
                'password' => 'Fy98765=',
                'role' => 'Manager',
                'address' => 'Avenida da República, 456',
                'city' => 'Porto, Porto',
                'zip_code' => '4000-098',
                'latitude' => 41.1579,
                'longitude' => -8.6291
            ],
            [
                'name' => 'Admin',
                'username' => 'admin369',
                'email' => 'admin@admin.com',
                'phone' => '+351 914 567 890',
                'password' => 'Fy98765=',
                'role' => 'Admin',
                'address' => 'Rua de Santa Catarina, 789',
                'city' => 'Braga, Braga',
                'zip_code' => '4700-320',
                'latitude' => 41.5518,
                'longitude' => -8.4229
            ],
            [
                'name' => 'Employee',
                'username' => 'employee369',
                'email' => 'employee@employee.com',
                'phone' => '+351 915 678 901',
                'password' => 'Fy98765=',
                'role' => 'Employee',
                'address' => 'Praça do Comércio, 321',
                'city' => 'Coimbra, Coimbra',
                'zip_code' => '3000-213',
                'latitude' => 40.2033,
                'longitude' => -8.4103
            ],
            [
                'name' => 'Customer',
                'username' => 'customer369',
                'email' => 'customer@customer.com',
                'phone' => '+351 916 789 012',
                'password' => 'Fy98765=',
                'role' => 'Customer',
                'address' => 'Rua Augusta, 654',
                'city' => 'Faro, Faro',
                'zip_code' => '8000-311',
                'latitude' => 37.0194,
                'longitude' => -7.9304
            ]
        ];

        foreach ($users as $userData) {
            // Crear usuario
            $user = User::factory()->create([
                'name' => $userData['name'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'uuid' => Uuid::uuid4()->toString(),
                'phone' => $userData['phone'],
                'password' => bcrypt($userData['password'])
            ]);
            
            // Asignar rol
            $user->assignRole(Role::where('name', $userData['role'])->where('guard_name', $guardName)->first());
            
            // Crear dirección
            UserAddress::create([
                'uuid' => Uuid::uuid4()->toString(),
                'user_id' => $user->id,
                'address' => $userData['address'],
                'city' => $userData['city'],
                'country' => 'Portugal',
                'zip_code' => $userData['zip_code'],
                'latitude' => $userData['latitude'],
                'longitude' => $userData['longitude'],
                'address_label_id' => $labels[0], // Usar el primer label disponible
                'principal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}