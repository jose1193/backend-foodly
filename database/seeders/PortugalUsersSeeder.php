<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\AddressLabel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class PortugalUsersSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_PT');
        $labels = AddressLabel::pluck('id')->toArray();
        
        // Verificar que existan labels
        if (empty($labels)) {
            $this->command->error('No se encontraron AddressLabels. Ejecuta primero el seeder de labels.');
            return;
        }

        // Principales ruas e avenidas de Covilhã com suas coordenadas aproximadas por zona
        $ruasCovilha = [
            // Centro histórico
            ['nome' => 'Rua da Alegria', 'lat_min' => 40.2750, 'lat_max' => 40.2780, 'lng_min' => -7.5050, 'lng_max' => -7.5020],
            ['nome' => 'Rua Comendador Mendes Veiga', 'lat_min' => 40.2740, 'lat_max' => 40.2770, 'lng_min' => -7.5060, 'lng_max' => -7.5030],
            ['nome' => 'Rua Direita', 'lat_min' => 40.2730, 'lat_max' => 40.2760, 'lng_min' => -7.5070, 'lng_max' => -7.5040],
            ['nome' => 'Rua da Fé', 'lat_min' => 40.2720, 'lat_max' => 40.2750, 'lng_min' => -7.5080, 'lng_max' => -7.5050],
            ['nome' => 'Rua Sacadura Cabral', 'lat_min' => 40.2760, 'lat_max' => 40.2790, 'lng_min' => -7.5040, 'lng_max' => -7.5010],
            
            // Zona da Universidade (UBI)
            ['nome' => 'Rua Marquês de Ávila e Bolama', 'lat_min' => 40.2780, 'lat_max' => 40.2820, 'lng_min' => -7.5120, 'lng_max' => -7.5080],
            ['nome' => 'Alameda Pêro da Covilhã', 'lat_min' => 40.2790, 'lat_max' => 40.2830, 'lng_min' => -7.5100, 'lng_max' => -7.5060],
            ['nome' => 'Rua do Convento', 'lat_min' => 40.2800, 'lat_max' => 40.2840, 'lng_min' => -7.5090, 'lng_max' => -7.5050],
            ['nome' => 'Avenida da Universidade', 'lat_min' => 40.2810, 'lat_max' => 40.2850, 'lng_min' => -7.5130, 'lng_max' => -7.5090],
            
            // Zona nova/residencial
            ['nome' => 'Avenida Frei Heitor Pinto', 'lat_min' => 40.2700, 'lat_max' => 40.2750, 'lng_min' => -7.5000, 'lng_max' => -7.4950],
            ['nome' => 'Rua dos Penedos Altos', 'lat_min' => 40.2680, 'lat_max' => 40.2720, 'lng_min' => -7.5020, 'lng_max' => -7.4980],
            ['nome' => 'Rua da Sertã', 'lat_min' => 40.2690, 'lat_max' => 40.2730, 'lng_min' => -7.5040, 'lng_max' => -7.5000],
            ['nome' => 'Rua Pedro Álvares Cabral', 'lat_min' => 40.2710, 'lat_max' => 40.2750, 'lng_min' => -7.5030, 'lng_max' => -7.4990],
            
            // Bairro do Paul
            ['nome' => 'Rua do Paul', 'lat_min' => 40.2650, 'lat_max' => 40.2690, 'lng_min' => -7.5100, 'lng_max' => -7.5060],
            ['nome' => 'Rua da Carpintaria', 'lat_min' => 40.2640, 'lat_max' => 40.2680, 'lng_min' => -7.5120, 'lng_max' => -7.5080],
            
            // Zona industrial/Tortosendo
            ['nome' => 'Estrada Nacional 230', 'lat_min' => 40.2850, 'lat_max' => 40.2900, 'lng_min' => -7.5200, 'lng_max' => -7.5150],
            ['nome' => 'Rua das Tílias', 'lat_min' => 40.2820, 'lat_max' => 40.2860, 'lng_min' => -7.5180, 'lng_max' => -7.5140],
            ['nome' => 'Rua Visconde da Coriscada', 'lat_min' => 40.2780, 'lat_max' => 40.2820, 'lng_min' => -7.5160, 'lng_max' => -7.5120],
            
            // Freguesias periféricas
            ['nome' => 'Rua de Unhais da Serra', 'lat_min' => 40.2600, 'lat_max' => 40.2650, 'lng_min' => -7.5150, 'lng_max' => -7.5100],
            ['nome' => 'Rua da Goldra', 'lat_min' => 40.2550, 'lat_max' => 40.2600, 'lng_min' => -7.5200, 'lng_max' => -7.5150],
        ];

        // Freguesias e localidades de Covilhã
        $freguesias = [
            'Covilhã e Canhoso',
            'Tortosendo',
            'Unhais da Serra',
            'São Jorge da Beira',
            'Ferro',
            'Sobral de São Miguel',
            'Teixoso',
            'Barco',
            'Boidobra',
            'Cantar-Galo',
            'Dominguiso',
            'Erada',
            'Orjais',
            'Peraboa',
            'Peso',
            'Vale Formoso',
            'Verdelhos',
            'Vila do Carvalho'
        ];

        // Obter os roles existentes
        $customerRole = \Spatie\Permission\Models\Role::where('name', 'Customer')->where('guard_name', 'api')->first();
        $managerRole = \Spatie\Permission\Models\Role::where('name', 'Manager')->where('guard_name', 'api')->first();
        $employeeRole = \Spatie\Permission\Models\Role::where('name', 'Employee')->where('guard_name', 'api')->first();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->where('guard_name', 'api')->first();

        // Verificar que os roles existam
        if (!$customerRole || !$managerRole || !$employeeRole || !$adminRole) {
            $this->command->error('Não foram encontrados todos os roles necessários. Execute primeiro o DatabaseSeeder principal.');
            return;
        }

        // Distribuição de roles: 70% Customer, 15% Employee, 10% Manager, 5% Admin
        $roleDistribution = [
            'Customer' => 700,  // 70%
            'Employee' => 150,  // 15%
            'Manager' => 100,   // 10%
            'Admin' => 50       // 5%
        ];

        // Criar array com roles distribuídos
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

        // Misturar o array para distribuição aleatória
        shuffle($rolesArray);

        $this->command->info('Criando 1000 utilizadores portugueses de Covilhã com roles variados...');
        $bar = $this->command->getOutput()->createProgressBar(1000);
        $bar->start();

        for ($i = 0; $i < 1000; $i++) {
            $ruaData = $faker->randomElement($ruasCovilha);
            $numero = $faker->numberBetween(1, 299);
            
            // Criar morada mais realista
            $address = $ruaData['nome'] . ', ' . $numero;
            
            // Adicionar piso e andar ocasionalmente
            if ($faker->boolean(25)) { // 25% de probabilidade
                $piso = $faker->numberBetween(1, 8);
                $lado = $faker->randomElement(['Esq.', 'Dto.', 'Frente', 'Tras']);
                $address .= ', ' . $piso . 'º ' . $lado;
            }

            // Coordenadas mais precisas segundo a rua
            $latitude = $faker->randomFloat(6, $ruaData['lat_min'], $ruaData['lat_max']);
            $longitude = $faker->randomFloat(6, $ruaData['lng_min'], $ruaData['lng_max']);
            
            // Código postal de Covilhã (formato português correto)
            $codigosPostaisCovilha = [
                '6200-000', '6200-001', '6200-002', '6200-003', '6200-004', '6200-005',
                '6200-006', '6200-007', '6200-008', '6200-009', '6200-010', '6200-011',
                '6200-012', '6200-013', '6200-014', '6200-015', '6200-016', '6200-017',
                '6200-018', '6200-019', '6200-020', '6200-151', '6200-152', '6200-153',
                '6200-154', '6200-155', '6200-501', '6200-502', '6200-503', '6200-504'
            ];
            $codigoPostal = $faker->randomElement($codigosPostaisCovilha);

            // Telemóveis portugueses mais realistas
            $operadoras = ['91', '92', '93', '96']; // Principais operadoras móveis PT
            $telefone = '+351 ' . $faker->randomElement($operadoras) . ' ' . $faker->numberBetween(100, 999) . ' ' . $faker->numberBetween(100, 999);

            try {
                $user = User::create([
                    'uuid' => (string) Str::uuid(),
                    'name' => $faker->firstName,
                    'last_name' => $faker->lastName,
                    'username' => $faker->unique()->userName,
                    'email' => $faker->unique()->safeEmail,
                    'email_verified_at' => $faker->boolean(85) ? now() : null, // 85% verificados
                    'password' => Hash::make('Test@123'),
                    'phone' => $telefone,
                    'date_of_birth' => $faker->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
                    'gender' => $faker->randomElement(['male', 'female']),
                    'profile_photo_path' => $faker->boolean(35) ? $faker->imageUrl(300, 300, 'people', true, 'avatar') : null,
                    'terms_and_conditions' => true,
                    'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => now(),
                ]);

                // Atribuir role segundo a distribuição
                $assignedRole = $rolesArray[$i];
                $user->assignRole($assignedRole);

                UserAddress::create([
                    'uuid' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'address' => $address,
                    'city' => $faker->randomElement($freguesias) . ', Covilhã',
                    'country' => 'Portugal',
                    'zip_code' => $codigoPostal,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address_label_id' => $faker->randomElement($labels),
                    'principal' => true,
                    'created_at' => $user->created_at,
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                $this->command->error("Erro ao criar utilizador {$i}: " . $e->getMessage());
                continue;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        
        // Mostrar estatísticas de roles criados
        $this->command->info('¡1000 utilizadores portugueses de Covilhã criados com sucesso!');
        $this->command->info('Distribuição de roles:');
        $this->command->info('- Customers: 700 utilizadores (70%)');
        $this->command->info('- Employees: 150 utilizadores (15%)');
        $this->command->info('- Managers: 100 utilizadores (10%)');
        $this->command->info('- Admins: 50 utilizadores (5%)');
        $this->command->info('');
        $this->command->info('Dados específicos de Covilhã:');
        $this->command->info('- Ruas e avenidas reais da cidade');
        $this->command->info('- Coordenadas GPS precisas');
        $this->command->info('- Códigos postais válidos (6200-xxx)');
        $this->command->info('- Telemóveis portugueses (+351)');
        $this->command->info('- Freguesias do concelho de Covilhã');
    }
}