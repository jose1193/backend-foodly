<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use App\Models\BusinessCoverImage;
use App\Models\BusinessHour;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PortugalBusinessSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('pt_PT');
        
        // Obter TODOS os utilizadores do seeder português (incluye Customers, Employees, Managers, Admins)
        $eligibleUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Customer', 'Manager', 'Admin', 'Employee'])
                  ->where('guard_name', 'api');
        })->pluck('id')->toArray();

        if (empty($eligibleUsers)) {
            $this->command->error('Não foram encontrados utilizadores elegíveis para ter negócios.');
            return;
        }

        $this->command->info('Utilizadores disponíveis: ' . count($eligibleUsers));

        // Obter categorias e serviços existentes
        $categories = Category::pluck('id')->toArray();
        $services = Service::pluck('id')->toArray();

        if (empty($categories) || empty($services)) {
            $this->command->error('Não foram encontradas categorias ou serviços. Execute primeiro o DatabaseSeeder.');
            return;
        }

        // Nomes de negócios típicos de Covilhã e região da Beira Interior por categoria
        $businessNames = [
            'Cocina Internacional' => [
                'Restaurante Mundial', 'Fusão Serra', 'Sabores do Mundo', 'Internacional Beira',
                'Cozinha Global', 'Mundo Gastronómico', 'Pratos Internacionais'
            ],
            'Comida Rápida' => [
                'Fast Food Serra', 'Quick Bite Covilhã', 'Rapid Grill', 'Speed Food',
                'Express Delivery', 'Comida Rápida 24h', 'Burger Express UBI'
            ],
            'Pizzerías' => [
                'Pizzaria Napolitana', 'Don Antonio Pizza', 'La Mozzarella', 'Pizzas da Serra',
                'Il Forno', 'Pizza Palace', 'A Clássica Pizzaria', 'Mama Mía Pizza'
            ],
            'Cocina Japonesa' => [
                'Sushi Covilhã', 'Sakura Restaurant', 'Nikkei Sushi Bar', 'Kyoto Grill',
                'Sushi UBI', 'Yamato Restaurant', 'Tokyo Express'
            ],
            'Carnes y Parrillas' => [
                'Churrasqueira do Paulo', 'O Assador da Beira', 'A Brasa', 'Churrasqueira da Serra',
                'O Espeto', 'Assado Capital', 'A Churrasqueira Tradicional', 'O Churrasco Português'
            ],
            'Fusión' => [
                'Fusão Beira', 'Cozinha de Autor', 'Mix Gourmet', 'Tendências Culinárias',
                'Laboratório Gastronómico', 'Fusion Lab Serra'
            ],
            'Cocina Vegetariana' => [
                'Verde Vida', 'Veggie Covilhã', 'Natural Kitchen', 'Bio Restaurante',
                'Jardim Vegano', 'Plantas e Sabores'
            ],
            'Cocina Mexicana' => [
                'Cantina Mexicana', 'Azteca Restaurant', 'Tacos e Mais', 'México Lindo',
                'La Hacienda', 'Viva México'
            ],
            'Cocina Koreana' => [
                'Seoul Kitchen', 'K-Food Covilhã', 'Gangnam Restaurant', 'Korea House',
                'Kimchi Palace', 'BBQ Coreano'
            ],
            'Cocina Portuguesa' => [
                'Tasca do Manuel', 'Restaurante Beirão', 'Bacalhau Real', 'Adega da Serra',
                'Casa Portuguesa', 'Lusitânia', 'O Typical', 'Taberna da Beira'
            ],
            'Pastelería y Postres' => [
                'Doce Tentação', 'Pastelaria Francesa', 'Sweet Dreams', 'A Confeitaria',
                'Confeitaria Central', 'Bolos & Mais', 'Delícia Doce', 'Pastéis da Serra'
            ],
            'Pubs y Vinerías' => [
                'The Irish Pub', 'Vinhos da Beira', 'Pub Britânico', 'Wine & Dine',
                'A Adega dos Vinhos', 'Cervejaria Artesanal', 'Tasca do Estudante'
            ],
            'Cafés y Desayunos' => [
                'Café Central', 'Coffee & Co', 'Pequenos-Almoços', 'Café da Esquina',
                'Morning Brew', 'Cafetaria Central', 'Café da UBI', 'Delta Café'
            ],
            'Mercados y Tiendas' => [
                'Mercado Gourmet', 'Loja Delicatessen', 'Market Plaza', 'Armazém Natural',
                'Gourmet Store', 'Produtos Seletos', 'Mercearia da Serra'
            ],
            'Escuelas de Cocina' => [
                'Academia Gastronómica', 'Escola de Chefs', 'Instituto Culinário',
                'Cozinha & Arte', 'Centro de Gastronomia'
            ]
        ];

        // Localizações de negócios em Covilhã e freguesias
        $businessLocations = [
            'Centro Histórico' => [
                ['address' => 'Rua da Alegria, 45', 'lat' => 40.2765, 'lng' => -7.5035],
                ['address' => 'Rua Comendador Mendes Veiga, 123', 'lat' => 40.2755, 'lng' => -7.5045],
                ['address' => 'Rua Direita, 67', 'lat' => 40.2745, 'lng' => -7.5055],
                ['address' => 'Rua da Fé, 89', 'lat' => 40.2735, 'lng' => -7.5065],
                ['address' => 'Rua Sacadura Cabral, 156', 'lat' => 40.2775, 'lng' => -7.5025],
                ['address' => 'Praça do Município, 12', 'lat' => 40.2760, 'lng' => -7.5040],
                ['address' => 'Largo das Eiras, 34', 'lat' => 40.2750, 'lng' => -7.5050],
                ['address' => 'Rua Visconde da Coriscada, 78', 'lat' => 40.2740, 'lng' => -7.5060],
            ],
            'Zona Universitária (UBI)' => [
                ['address' => 'Rua Marquês de Ávila e Bolama, 234', 'lat' => 40.2800, 'lng' => -7.5100],
                ['address' => 'Alameda Pêro da Covilhã, 567', 'lat' => 40.2810, 'lng' => -7.5080],
                ['address' => 'Rua do Convento, 45', 'lat' => 40.2820, 'lng' => -7.5070],
                ['address' => 'Avenida da Universidade, 123', 'lat' => 40.2830, 'lng' => -7.5110],
                ['address' => 'Calçada Fonte do Lameiro, 89', 'lat' => 40.2825, 'lng' => -7.5095],
                ['address' => 'Rua da Biblioteca, 67', 'lat' => 40.2815, 'lng' => -7.5085],
                ['address' => 'Campus Universitário, Bloco A', 'lat' => 40.2835, 'lng' => -7.5120],
            ],
            'Zona Nova/Residencial' => [
                ['address' => 'Avenida Frei Heitor Pinto, 345', 'lat' => 40.2725, 'lng' => -7.4975],
                ['address' => 'Rua dos Penedos Altos, 123', 'lat' => 40.2700, 'lng' => -7.5000],
                ['address' => 'Rua da Sertã, 456', 'lat' => 40.2710, 'lng' => -7.5020],
                ['address' => 'Rua Pedro Álvares Cabral, 789', 'lat' => 40.2730, 'lng' => -7.5010],
                ['address' => 'Rua dos Descobrimentos, 234', 'lat' => 40.2715, 'lng' => -7.4990],
                ['address' => 'Avenida 25 de Abril, 567', 'lat' => 40.2720, 'lng' => -7.4985],
            ],
            'Bairro do Paul' => [
                ['address' => 'Rua do Paul, 123', 'lat' => 40.2670, 'lng' => -7.5080],
                ['address' => 'Rua da Carpintaria, 45', 'lat' => 40.2660, 'lng' => -7.5100],
                ['address' => 'Largo do Paul, 67', 'lat' => 40.2675, 'lng' => -7.5085],
                ['address' => 'Travessa do Paul, 89', 'lat' => 40.2665, 'lng' => -7.5095],
            ],
            'Tortosendo' => [
                ['address' => 'Estrada Nacional 230, 234', 'lat' => 40.2875, 'lng' => -7.5175],
                ['address' => 'Rua das Tílias, 456', 'lat' => 40.2840, 'lng' => -7.5160],
                ['address' => 'Avenida da Liberdade, 123', 'lat' => 40.2860, 'lng' => -7.5170],
                ['address' => 'Rua Principal de Tortosendo, 567', 'lat' => 40.2850, 'lng' => -7.5165],
                ['address' => 'Largo de Tortosendo, 89', 'lat' => 40.2845, 'lng' => -7.5155],
            ],
            'Unhais da Serra' => [
                ['address' => 'Rua de Unhais da Serra, 123', 'lat' => 40.2625, 'lng' => -7.5125],
                ['address' => 'Avenida Termal, 45', 'lat' => 40.2615, 'lng' => -7.5135],
                ['address' => 'Rua das Termas, 67', 'lat' => 40.2635, 'lng' => -7.5115],
                ['address' => 'Largo das Caldas, 89', 'lat' => 40.2620, 'lng' => -7.5130],
            ],
            'São Jorge da Beira' => [
                ['address' => 'Rua de São Jorge, 234', 'lat' => 40.2580, 'lng' => -7.5180],
                ['address' => 'Estrada de São Jorge, 456', 'lat' => 40.2570, 'lng' => -7.5190],
                ['address' => 'Largo de São Jorge, 123', 'lat' => 40.2575, 'lng' => -7.5185],
            ],
            'Teixoso' => [
                ['address' => 'Rua Principal de Teixoso, 345', 'lat' => 40.3050, 'lng' => -7.4950],
                ['address' => 'Avenida de Teixoso, 567', 'lat' => 40.3040, 'lng' => -7.4960],
                ['address' => 'Largo de Teixoso, 123', 'lat' => 40.3045, 'lng' => -7.4955],
            ]
        ];

        $freguesias = array_keys($businessLocations);
        
        // Distribuição de negócios por role de utilizador (igual ao argentino)
        $usersByRole = [
            'Customer' => User::whereHas('roles', function($query) {
                $query->where('name', 'Customer')->where('guard_name', 'api');
            })->pluck('id')->toArray(),
            'Employee' => User::whereHas('roles', function($query) {
                $query->where('name', 'Employee')->where('guard_name', 'api');
            })->pluck('id')->toArray(),
            'Manager' => User::whereHas('roles', function($query) {
                $query->where('name', 'Manager')->where('guard_name', 'api');
            })->pluck('id')->toArray(),
        ];

        $businessesToCreate = [];
        
        // Customers: 50% têm 1 negócio (350 negócios)
        $selectedCustomers = $faker->randomElements($usersByRole['Customer'], intval(count($usersByRole['Customer']) * 0.5));
        foreach ($selectedCustomers as $userId) {
            $businessesToCreate[] = $userId;
        }

        // Employees: cada um tem 1 negócio (150 negócios)
        foreach ($usersByRole['Employee'] as $userId) {
            $businessesToCreate[] = $userId;
        }

        // Managers: cada um tem 2 negócios (200 negócios)
        foreach ($usersByRole['Manager'] as $userId) {
            $businessesToCreate[] = $userId;
            $businessesToCreate[] = $userId; // Segundo negócio
        }

        // Misturar para distribuição aleatória
        shuffle($businessesToCreate);
        
        $totalBusinesses = count($businessesToCreate);
        
        // Criar negócios distribuídos entre utilizadores do seeder português
        $this->command->info("Criando {$totalBusinesses} negócios em Covilhã...");
        $this->command->info('Distribuição: ~350 Customers, 150 Employees, 200 Managers');
        $bar = $this->command->getOutput()->createProgressBar($totalBusinesses);
        $bar->start();

        for ($i = 0; $i < $totalBusinesses; $i++) {
            try {
                $categoryId = $faker->randomElement($categories);
                $category = Category::find($categoryId);
                $categoryName = $category->category_name;
                
                // Obter nomes segundo a categoria
                $availableNames = $businessNames[$categoryName] ?? ['Restaurante Genérico', 'Comida & Sabor', 'Gastronomia Covilhã'];
                $businessName = $faker->randomElement($availableNames);
                
                // Agregar variação ao nome para evitar duplicados
                if ($faker->boolean(30)) {
                    $freguesia = $faker->randomElement($freguesias);
                    $businessName .= ' - ' . $freguesia;
                }
                
                // Selecionar localização aleatória
                $freguesia = $faker->randomElement($freguesias);
                $location = $faker->randomElement($businessLocations[$freguesia]);
                
                // Código postal de Covilhã
                $zipCodes = ['6200-001', '6200-002', '6200-003', '6200-151', '6200-152', '6200-501'];
                
                // Criar o negócio
                $business = Business::create([
                    'business_uuid' => (string) Str::uuid(),
                    'business_logo' => $faker->boolean(70) ? 'https://foodly.s3.amazonaws.com/public/business_logos/logo' . $faker->numberBetween(1, 100) . '.jpg' : null,
                    'business_name' => $businessName,
                    'business_email' => strtolower(str_replace([' ', '-'], ['', ''], $businessName)) . '@business.pt',
                    'business_phone' => '+351 275 ' . $faker->numberBetween(100, 999) . ' ' . $faker->numberBetween(100, 999),
                    'business_address' => $location['address'],
                    'business_zipcode' => $faker->randomElement($zipCodes),
                    'business_city' => $freguesia . ', Covilhã',
                    'business_country' => 'Portugal',
                    'business_website' => $faker->boolean(40) ? 'www.' . strtolower(str_replace([' ', '-'], ['', ''], $businessName)) . '.pt' : null,
                    'business_about_us' => $this->generateBusinessDescription($categoryName, $faker),
                    'business_additional_info' => $faker->boolean(30) ? 'Aceitamos todos os cartões de crédito. Estacionamento disponível.' : null,
                    'business_latitude' => $location['lat'],
                    'business_longitude' => $location['lng'],
                    'category_id' => $categoryId,
                    'user_id' => $businessesToCreate[$i], // Utilizador específico segundo distribuição
                ]);

                // Criar horários comerciais
                $this->createBusinessHours($business->id, $faker);

                // Atribuir serviços aleatórios (2-6 serviços por negócio)
                $randomServices = $faker->randomElements($services, $faker->numberBetween(2, 6));
                $business->services()->attach($randomServices);

                // Criar imagens de capa (1-4 imagens)
                $this->createCoverImages($business->id, $faker);

                // Criar promoções ocasionalmente (30% de probabilidade)
                if ($faker->boolean(30)) {
                    $this->createPromotion($business->id, $faker);
                }

            } catch (\Exception $e) {
                $this->command->error("Erro ao criar negócio {$i}: " . $e->getMessage());
                continue;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("¡{$totalBusinesses} negócios criados com sucesso em Covilhã!");
        $this->command->info('Distribuição final:');
        $this->command->info('- ~350 negócios de Customers (50% de customers têm 1 negócio)');
        $this->command->info('- 150 negócios de Employees (100% têm 1 negócio)');  
        $this->command->info('- 200 negócios de Managers (100% têm 2 negócios)');
        $this->command->info('- Distribuídos em 8 freguesias de Covilhã');
        $this->command->info('- Com horários, serviços, imagens e promoções');
    }

    private function generateBusinessDescription($categoryName, $faker)
    {
        $descriptions = [
            'Cocina Internacional' => 'Restaurante de cozinha internacional com pratos de todo o mundo. Ambiente acolhedor e carta variada.',
            'Comida Rápida' => 'Local de comida rápida com entrega ao domicílio e take away. Hambúrgueres, batatas fritas e bebidas.',
            'Pizzerías' => 'Pizzaria tradicional com massa caseira e ingredientes frescos. Especialidade em pizzas ao forno a lenha.',
            'Cocina Japonesa' => 'Restaurante japonês autêntico. Sushi, sashimi e pratos quentes tradicionais.',
            'Carnes y Parrillas' => 'Churrasqueira portuguesa tradicional. Carnes premium grelhadas e vinhos da região.',
            'Fusión' => 'Cozinha de autor com técnicas modernas. Experiência gastronómica única.',
            'Cocina Vegetariana' => 'Restaurante vegetariano e vegano. Ingredientes biológicos e pratos saudáveis.',
            'Cocina Mexicana' => 'Autêntica comida mexicana. Tacos, quesadillas e especialidades picantes.',
            'Cocina Koreana' => 'Restaurante coreano tradicional. BBQ coreano e pratos típicos da Coreia.',
            'Cocina Portuguesa' => 'Cozinha portuguesa caseira. Bacalhau, pastéis de nata e vinhos do Porto.',
            'Pastelería y Postres' => 'Pastelaria artesanal. Bolos, pastéis e café de especialidade.',
            'Pubs y Vinerías' => 'Pub com ambiente descontraído. Cervejas artesanais e vinhos de adega.',
            'Cafés y Desayunos' => 'Cafetaria tradicional portuguesa. Pequenos-almoços completos e café de especialidade.',
            'Mercados y Tiendas' => 'Loja gourmet com produtos seletos. Queijos, enchidos e delicatessen.',
            'Escuelas de Cocina' => 'Escola de cozinha profissional. Cursos e workshops gastronómicos.'
        ];

        return $descriptions[$categoryName] ?? 'Excelente gastronomia no coração da Serra da Estrela. Ambiente familiar e preços acessíveis.';
    }

    private function createBusinessHours($businessId, $faker)
    {
        // Horários típicos para diferentes dias (portugueses)
        $schedules = [
            'restaurant' => [
                ['open_a' => '12:00', 'close_a' => '15:00', 'open_b' => '19:00', 'close_b' => '23:00'],
                ['open_a' => '11:30', 'close_a' => '23:30', 'open_b' => null, 'close_b' => null],
            ],
            'cafe' => [
                ['open_a' => '08:00', 'close_a' => '20:00', 'open_b' => null, 'close_b' => null],
                ['open_a' => '07:30', 'close_a' => '22:00', 'open_b' => null, 'close_b' => null],
            ],
            'fast_food' => [
                ['open_a' => '11:00', 'close_a' => '01:00', 'open_b' => null, 'close_b' => null],
                ['open_a' => '10:00', 'close_a' => '24:00', 'open_b' => null, 'close_b' => null],
            ]
        ];

        $schedule = $faker->randomElement($schedules['restaurant']);

        for ($day = 0; $day < 7; $day++) {
            // Alguns negócios fecham às segundas-feiras
            if ($day == 1 && $faker->boolean(25)) {
                BusinessHour::create([
                    'business_id' => $businessId,
                    'day' => $day,
                    'open_a' => null,
                    'close_a' => null,
                    'open_b' => null,
                    'close_b' => null,
                ]);
            } else {
                // Sextas e sábados podem ter horários estendidos
                $extendedHours = ($day >= 5 && $day <= 6) && $faker->boolean(50);
                
                BusinessHour::create([
                    'business_id' => $businessId,
                    'day' => $day,
                    'open_a' => $schedule['open_a'],
                    'close_a' => $extendedHours ? '24:00' : $schedule['close_a'],
                    'open_b' => $schedule['open_b'],
                    'close_b' => $schedule['close_b'],
                ]);
            }
        }
    }

    private function createCoverImages($businessId, $faker)
    {
        $imageCount = $faker->numberBetween(1, 4);
        
        for ($i = 0; $i < $imageCount; $i++) {
            BusinessCoverImage::create([
                'business_image_uuid' => (string) Str::uuid(),
                'business_image_path' => 'https://foodly.s3.amazonaws.com/public/business_photos/business' . $businessId . '_' . ($i + 1) . '.jpg',
                'business_id' => $businessId,
            ]);
        }
    }

    private function createPromotion($businessId, $faker)
    {
        $promoTitles = [
            'Happy Hour 2x1', 'Desconto 20%', 'Menu Degustação', 'Promo Almoço',
            'Desconto Estudantes UBI', '2x1 em Pizzas', 'Noite de Vinhos', 'Menu Executivo',
            'Francesinha + Imperial', 'Bifana do Dia'
        ];

        $promoDescriptions = [
            'De segunda a sexta das 15h às 18h. Não perca!',
            'Desconto especial todas as terças e quintas-feiras.',
            'Menu especial com entrada, prato principal e sobremesa.',
            'Promoção válida das 12h às 16h de segunda a sexta-feira.',
            'Desconto especial para estudantes da UBI com cartão.',
        ];

        Promotion::create([
            'uuid' => (string) Str::uuid(),
            'title' => $faker->randomElement($promoTitles),
            'sub_title' => '¡Oferta especial!',
            'description' => $faker->randomElement($promoDescriptions),
            'start_date' => now(),
            'expire_date' => now()->addMonths(2),
            'versions' => [],
            'prices' => [
                'regular' => 0,
                'medium' => 0,
                'big' => 0
            ],
            'available' => true,
            'business_id' => $businessId,
        ]);
    }
}