<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class MockUsersSeeder extends Seeder
{
    public function run()
    {
        // Lista de archivos JSON a procesar
        $jsonFiles = [
            __DIR__ . '/mock-users-arg-caba-100-199.json',
            __DIR__ . '/mock-users-arg-caba-200-299.json'
        ];

            // Solo ejecutar comandos MySQL si el driver es mysql
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                DB::statement('ALTER TABLE users AUTO_INCREMENT = 100;');
            }

        foreach ($jsonFiles as $jsonPath) {
            try {
                \Log::info("Procesando archivo: " . $jsonPath);
                
                if (!file_exists($jsonPath)) {
                    \Log::error("El archivo no existe: " . $jsonPath);
                    continue;
                }

                $jsonData = json_decode(file_get_contents($jsonPath), true);
                \Log::info("Número de usuarios a crear desde " . basename($jsonPath) . ": " . count($jsonData['users']));

                foreach ($jsonData['users'] as $userData) {
                    try {
                        $user = User::create([
                            'uuid' => $userData['uuid'],
                            'profile_photo_path' => $userData['photo'],
                            'name' => $userData['name'],
                            'last_name' => $userData['last_name'],
                            'username' => $userData['username'],
                            'email' => $userData['email'],
                            'email_verified_at' => $userData['email_verified_at'],
                            'date_of_birth' => $userData['date_of_birth'],
                            'phone' => $userData['phone'],
                            'terms_and_conditions' => $userData['terms_and_conditions'],
                            'gender' => $userData['gender'],
                            'password' => Hash::make('Test@123'),
                            'created_at' => $userData['created_at'],
                            'updated_at' => $userData['updated_at'],
                            'deleted_at' => $userData['deleted_at'],
                        ]);

                        // Asignar rol con guard api específicamente
                        if ($userData['user_role'] === 'Customer') {
                            \Log::info("Assigning role Customer to user: " . $user->username);
                            $customerRole = Role::where('name', 'Customer')
                                             ->where('guard_name', 'api')
                                             ->first();
                            
                            if ($customerRole) {
                                $user->assignRole($customerRole);
                            } else {
                                \Log::error("Role Customer not found for guard api");
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error al crear usuario ID {$userData['id']}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                \Log::error("Error procesando archivo {$jsonPath}: " . $e->getMessage());
            }
        }

            // Solo ejecutar en MySQL
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
    }
}
