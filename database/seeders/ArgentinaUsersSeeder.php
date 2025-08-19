<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\AddressLabel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class ArgentinaUsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_AR');
        $labels = AddressLabel::pluck('id')->toArray();
        
        // Verificar que existan labels
        if (empty($labels)) {
            $this->command->error('No se encontraron AddressLabels. Ejecuta primero el seeder de labels.');
            return;
        }

        // Avenidas principales de CABA con sus coordenadas aproximadas por zona
        $avenidasConZonas = [
            // Centro/Microcentro
            ['nombre' => 'Rivadavia', 'lat_min' => -34.6200, 'lat_max' => -34.6100, 'lng_min' => -58.3900, 'lng_max' => -58.3700],
            ['nombre' => 'Corrientes', 'lat_min' => -34.6050, 'lat_max' => -34.6000, 'lng_min' => -58.3850, 'lng_max' => -58.3650],
            ['nombre' => 'Santa Fe', 'lat_min' => -34.5950, 'lat_max' => -34.5900, 'lng_min' => -58.3900, 'lng_max' => -58.3700],
            
            // Palermo/Recoleta
            ['nombre' => 'Callao', 'lat_min' => -34.5950, 'lat_max' => -34.5850, 'lng_min' => -58.3950, 'lng_max' => -58.3900],
            ['nombre' => 'Pueyrredón', 'lat_min' => -34.5900, 'lat_max' => -34.5800, 'lng_min' => -58.4000, 'lng_max' => -58.3950],
            ['nombre' => 'Scalabrini Ortiz', 'lat_min' => -34.5900, 'lat_max' => -34.5750, 'lng_min' => -58.4150, 'lng_max' => -58.4050],
            ['nombre' => 'Juan B. Justo', 'lat_min' => -34.6000, 'lat_max' => -34.5700, 'lng_min' => -58.4400, 'lng_max' => -58.4200],
            
            // Caballito/Villa Crespo
            ['nombre' => 'Directorio', 'lat_min' => -34.6150, 'lat_max' => -34.6050, 'lng_min' => -58.4500, 'lng_max' => -58.4300],
            ['nombre' => 'Díaz Vélez', 'lat_min' => -34.6100, 'lat_max' => -34.6000, 'lng_min' => -58.4600, 'lng_max' => -58.4400],
            ['nombre' => 'Nazca', 'lat_min' => -34.6200, 'lat_max' => -34.6000, 'lng_min' => -58.4800, 'lng_max' => -58.4600],
            
            // Norte (Belgrano/Núñez)
            ['nombre' => 'Cabildo', 'lat_min' => -34.5800, 'lat_max' => -34.5400, 'lng_min' => -58.4700, 'lng_max' => -58.4400],
            ['nombre' => 'Del Libertador', 'lat_min' => -34.5700, 'lat_max' => -34.5500, 'lng_min' => -58.4200, 'lng_max' => -58.4000],
            ['nombre' => 'Monroe', 'lat_min' => -34.5600, 'lat_max' => -34.5500, 'lng_min' => -58.4500, 'lng_max' => -58.4300],
            
            // Sur (San Telmo/Barracas)
            ['nombre' => 'Independencia', 'lat_min' => -34.6200, 'lat_max' => -34.6150, 'lng_min' => -58.3900, 'lng_max' => -58.3700],
            ['nombre' => 'San Juan', 'lat_min' => -34.6250, 'lat_max' => -34.6200, 'lng_min' => -58.3900, 'lng_max' => -58.3700],
            ['nombre' => 'Boedo', 'lat_min' => -34.6300, 'lat_max' => -34.6200, 'lng_min' => -58.4200, 'lng_max' => -58.4000],
            
            // Oeste (Flores/Floresta)
            ['nombre' => 'Gaona', 'lat_min' => -34.6400, 'lat_max' => -34.6200, 'lng_min' => -58.5000, 'lng_max' => -58.4700],
            ['nombre' => 'San Martín', 'lat_min' => -34.6200, 'lat_max' => -34.6000, 'lng_min' => -58.5200, 'lng_max' => -58.4800],
            ['nombre' => 'Álvarez Jonte', 'lat_min' => -34.6300, 'lat_max' => -34.6100, 'lng_min' => -58.5100, 'lng_max' => -58.4800],
        ];

        // Barrios de CABA para mayor realismo
        $barriosCaba = [
            'Palermo', 'Recoleta', 'Belgrano', 'Villa Crespo', 'Caballito', 'Flores',
            'San Telmo', 'La Boca', 'Puerto Madero', 'Retiro', 'Microcentro',
            'Barracas', 'San Cristóbal', 'Balvanera', 'Almagro', 'Villa Urquiza',
            'Colegiales', 'Chacarita', 'Villa Devoto', 'Floresta', 'Núñez'
        ];

        // Obtener los roles existentes
        $customerRole = \Spatie\Permission\Models\Role::where('name', 'Customer')->where('guard_name', 'api')->first();
        $managerRole = \Spatie\Permission\Models\Role::where('name', 'Manager')->where('guard_name', 'api')->first();
        $employeeRole = \Spatie\Permission\Models\Role::where('name', 'Employee')->where('guard_name', 'api')->first();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->where('guard_name', 'api')->first();

        // Verificar que los roles existan
        if (!$customerRole || !$managerRole || !$employeeRole || !$adminRole) {
            $this->command->error('No se encontraron todos los roles necesarios. Ejecuta primero el DatabaseSeeder principal.');
            return;
        }

        // Distribución de roles: 70% Customer, 15% Employee, 10% Manager, 5% Admin
        $roleDistribution = [
            'Customer' => 700,  // 70%
            'Employee' => 150,  // 15%
            'Manager' => 100,   // 10%
            'Admin' => 50       // 5%
        ];

        // Crear array con roles distribuidos
        $rolesArray = [];
        for ($i = 0; $i < $roleDistribution['Customer']; $i++) {
            $rolesArray[] = $customerRole;
        }
        for ($i = 0; $i < $roleDistribution['Employee']; $i++) {
            $rolesArray[] = $employeeRole;
        }
        for ($i = 0; $i < $roleDistribution['Manager']; $i++) {
            $rolesArray[] = $managerRole;
        }
        for ($i = 0; $i < $roleDistribution['Admin']; $i++) {
            $rolesArray[] = $adminRole;
        }

        // Mezclar el array para distribución aleatoria
        shuffle($rolesArray);

        $this->command->info('Creando 1000 usuarios argentinos con roles variados...');
        $bar = $this->command->getOutput()->createProgressBar(1000);
        $bar->start();

        for ($i = 0; $i < 1000; $i++) {
            $avenidaData = $faker->randomElement($avenidasConZonas);
            $numero = $faker->numberBetween(100, 9999);
            
            // Crear dirección más realista
            $tipoVia = $faker->randomElement(['Av.', 'Calle', '']);
            $address = trim($tipoVia . ' ' . $avenidaData['nombre'] . ' ' . $numero);
            
            // Agregar piso y departamento ocasionalmente
            if ($faker->boolean(30)) { // 30% de probabilidad
                $piso = $faker->numberBetween(1, 20);
                $depto = $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F']);
                $address .= ', Piso ' . $piso . ' Depto ' . $depto;
            }

            // Coordenadas más precisas según la avenida
            $latitude = $faker->randomFloat(6, $avenidaData['lat_min'], $avenidaData['lat_max']);
            $longitude = $faker->randomFloat(6, $avenidaData['lng_min'], $avenidaData['lng_max']);
            
            // Código postal de CABA (formato correcto)
            $codigosPostalesCaba = ['C1000', 'C1001', 'C1002', 'C1003', 'C1004', 'C1005', 
                                    'C1006', 'C1007', 'C1008', 'C1009', 'C1010', 'C1011',
                                    'C1012', 'C1013', 'C1014', 'C1015', 'C1016', 'C1017',
                                    'C1018', 'C1019', 'C1020', 'C1021', 'C1022', 'C1023'];
            $zip = $faker->randomElement($codigosPostalesCaba) . $faker->randomElement(['AAA', 'AAB', 'AAC', 'AAD']);

            // Teléfonos argentinos más realistas
            $codigosArea = ['011', '011']; // CABA usa 011
            $telefono = '+54 ' . $faker->randomElement($codigosArea) . ' ' . $faker->numberBetween(1000, 9999) . '-' . $faker->numberBetween(1000, 9999);

            try {
                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'username' => $faker->unique()->userName,
                    'email' => $faker->unique()->safeEmail,
                    'email_verified_at' => $faker->boolean(80) ? now() : null, // 80% verificados
                    'password' => Hash::make('Test@123'),
                    'phone' => $telefono,
                    'date_of_birth' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'profile_photo_path' => $faker->boolean(40) ? $faker->imageUrl(300, 300, 'people', true, 'avatar') : null,
                    'terms_and_conditions' => true,
                    'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => now(),
                ]);

                // Asignar rol según la distribución
                $assignedRole = $rolesArray[$i];
                $user->assignRole($assignedRole);

                UserAddress::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'address' => $address,
                    'city' => $faker->randomElement($barriosCaba) . ', Ciudad Autónoma de Buenos Aires',
                    'country' => 'Argentina',
                    'zip_code' => $zip,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address_label_id' => $faker->randomElement($labels),
                    'principal' => true,
                    'created_at' => $user->created_at,
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                $this->command->error("Error creando usuario {$i}: " . $e->getMessage());
                continue;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        
        // Mostrar estadísticas de roles creados
        $this->command->info('¡1000 usuarios argentinos creados exitosamente con direcciones reales de CABA!');
        $this->command->info('Distribución de roles:');
        $this->command->info('- Customers: 700 usuarios (70%)');
        $this->command->info('- Employees: 150 usuarios (15%)');
        $this->command->info('- Managers: 100 usuarios (10%)');
        $this->command->info('- Admins: 50 usuarios (5%)');
    }
}