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

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_AR');
        
        // Obtener TODOS los usuarios del seeder argentino (incluye Customers, Employees, Managers, Admins)
        $eligibleUsers = User::whereHas('roles', function($query) {
            $query->whereIn('name', ['Customer', 'Manager', 'Admin', 'Employee'])
                  ->where('guard_name', 'api');
        })->pluck('id')->toArray();

        if (empty($eligibleUsers)) {
            $this->command->error('No se encontraron usuarios elegibles para tener negocios.');
            return;
        }

        $this->command->info('Usuarios disponibles: ' . count($eligibleUsers));

        // Obtener categorías y servicios existentes
        $categories = Category::pluck('id')->toArray();
        $services = Service::pluck('id')->toArray();

        if (empty($categories) || empty($services)) {
            $this->command->error('No se encontraron categorías o servicios. Ejecuta primero el DatabaseSeeder.');
            return;
        }

        // Nombres de negocios típicos de Buenos Aires por categoría
        $businessNames = [
            'Cocina Internacional' => [
                'Bistró Mundial', 'Fusion Capital', 'Sabores del Mundo', 'Internacional Gourmet',
                'Cocina Global BA', 'Mundo Gastronómico', 'Platillos Internacionales'
            ],
            'Comida Rápida' => [
                'Burger Express', 'Fast Food Porteño', 'Quick Bite', 'Rapid Grill',
                'Speed Food', 'Express Delivery', 'Comida Rápida 24hs'
            ],
            'Pizzerías' => [
                'Pizzería Napoletana', 'Don Antonio Pizza', 'La Mozzarella', 'Pizzas del Centro',
                'Il Forno', 'Pizza Palace', 'La Clásica Pizzería', 'Mama Mía Pizza'
            ],
            'Cocina Japonesa' => [
                'Sushi Palermo', 'Sakura Restaurant', 'Nikkei Sushi Bar', 'Kyoto Grill',
                'Sushi Recoleta', 'Yamato Restaurant', 'Tokyo Express'
            ],
            'Carnes y Parrillas' => [
                'Parrilla Don Carlos', 'El Asador Porteño', 'La Cabrera', 'Parrilla del Barrio',
                'El Quincho', 'Asado Capital', 'La Parrilla Criolla', 'El Fogón Argentino'
            ],
            'Fusión' => [
                'Fusión BA', 'Cocina de Autor', 'Mix Gourmet', 'Tendencias Culinarias',
                'Laboratorio Gastronómico', 'Fusion Lab'
            ],
            'Cocina Vegetariana' => [
                'Verde Vida', 'Veggie Capital', 'Natural Kitchen', 'Bio Restaurante',
                'Jardín Vegano', 'Plantas y Sabores'
            ],
            'Cocina Mexicana' => [
                'Cantina Mexicana', 'Azteca Restaurant', 'Tacos y Más', 'México Lindo',
                'La Hacienda', 'Viva México'
            ],
            'Cocina Koreana' => [
                'Seoul Kitchen', 'K-Food BA', 'Gangnam Restaurant', 'Korea House',
                'Kimchi Palace', 'BBQ Coreano'
            ],
            'Cocina Portuguesa' => [
                'Porto Bello', 'Lisboa Restaurant', 'Bacalao Real', 'Fado Gastronómico',
                'Casa Portuguesa', 'Lusitania'
            ],
            'Pastelería y Postres' => [
                'Dulce Tentación', 'Pastelería Francesa', 'Sweet Dreams', 'La Repostería',
                'Confitería Central', 'Tortas & Más', 'Delicia Dulce'
            ],
            'Pubs y Vinerías' => [
                'The Irish Pub', 'Vinoteca Palermo', 'Pub Británico', 'Wine & Dine',
                'La Cava de los Vinos', 'Cervecería Artesanal'
            ],
            'Cafés y Desayunos' => [
                'Café Tortoni Jr.', 'Coffee & Co', 'Desayunos Porteños', 'Café de la Esquina',
                'Morning Brew', 'Cafetería Central', 'Café del Barrio'
            ],
            'Mercados y Tiendas' => [
                'Mercado Gourmet', 'Tienda Delicatessen', 'Market Plaza', 'Almacén Natural',
                'Gourmet Store', 'Productos Selectos'
            ],
            'Escuelas de Cocina' => [
                'Academia Gastronómica', 'Escuela de Chefs', 'Instituto Culinario',
                'Cocina & Arte', 'Centro de Gastronomía'
            ]
        ];

        // Más direcciones para cubrir 700 negocios
        $businessLocations = [
            'Palermo' => [
                ['address' => 'Av. Santa Fe 3200', 'lat' => -34.5889, 'lng' => -58.3999],
                ['address' => 'Av. Córdoba 5500', 'lat' => -34.5991, 'lng' => -58.4201],
                ['address' => 'Thames 1800', 'lat' => -34.5851, 'lng' => -58.4251],
                ['address' => 'Av. Cabildo 2500', 'lat' => -34.5701, 'lng' => -58.4501],
                ['address' => 'Gorriti 4800', 'lat' => -34.5871, 'lng' => -58.4321],
                ['address' => 'Honduras 3900', 'lat' => -34.5891, 'lng' => -58.4181],
                ['address' => 'Armenia 1700', 'lat' => -34.5831, 'lng' => -58.4291],
                ['address' => 'Serrano 1500', 'lat' => -34.5811, 'lng' => -58.4231],
            ],
            'Recoleta' => [
                ['address' => 'Av. Callao 1200', 'lat' => -34.5951, 'lng' => -58.3921],
                ['address' => 'Av. Pueyrredón 1500', 'lat' => -34.5881, 'lng' => -58.3981],
                ['address' => 'Junín 1300', 'lat' => -34.5901, 'lng' => -58.3851],
                ['address' => 'Montevideo 1000', 'lat' => -34.5961, 'lng' => -58.3881],
                ['address' => 'Ayacucho 1800', 'lat' => -34.5921, 'lng' => -58.3761],
                ['address' => 'Rodriguez Peña 1400', 'lat' => -34.5941, 'lng' => -58.3841],
            ],
            'San Telmo' => [
                ['address' => 'Defensa 900', 'lat' => -34.6181, 'lng' => -58.3721],
                ['address' => 'Estados Unidos 800', 'lat' => -34.6201, 'lng' => -58.3741],
                ['address' => 'Av. San Juan 350', 'lat' => -34.6221, 'lng' => -58.3681],
                ['address' => 'Bolivar 600', 'lat' => -34.6161, 'lng' => -58.3741],
                ['address' => 'Balcarce 700', 'lat' => -34.6141, 'lng' => -58.3701],
                ['address' => 'Humberto Primo 500', 'lat' => -34.6181, 'lng' => -58.3681],
            ],
            'Puerto Madero' => [
                ['address' => 'Av. Alicia Moreau de Justo 1750', 'lat' => -34.6101, 'lng' => -58.3621],
                ['address' => 'Av. Eduardo Madero 1200', 'lat' => -34.6081, 'lng' => -58.3641],
                ['address' => 'Pierina Dealessi 750', 'lat' => -34.6121, 'lng' => -58.3601],
                ['address' => 'Juana Manso 1200', 'lat' => -34.6061, 'lng' => -58.3661],
            ],
            'Villa Crespo' => [
                ['address' => 'Av. Corrientes 4500', 'lat' => -34.5981, 'lng' => -58.4321],
                ['address' => 'Av. Warnes 1200', 'lat' => -34.5921, 'lng' => -58.4281],
                ['address' => 'Thames 900', 'lat' => -34.5901, 'lng' => -58.4341],
                ['address' => 'Acevedo 800', 'lat' => -34.5941, 'lng' => -58.4361],
                ['address' => 'Murillo 700', 'lat' => -34.5961, 'lng' => -58.4301],
            ],
            'Caballito' => [
                ['address' => 'Av. Rivadavia 5000', 'lat' => -34.6181, 'lng' => -58.4321],
                ['address' => 'Av. Directorio 1800', 'lat' => -34.6121, 'lng' => -58.4401],
                ['address' => 'Av. Díaz Vélez 4500', 'lat' => -34.6081, 'lng' => -58.4381],
                ['address' => 'Pedro Goyena 900', 'lat' => -34.6141, 'lng' => -58.4461],
                ['address' => 'Acoyte 600', 'lat' => -34.6161, 'lng' => -58.4441],
            ],
            'Belgrano' => [
                ['address' => 'Av. Cabildo 1500', 'lat' => -34.5621, 'lng' => -58.4581],
                ['address' => 'Av. Monroe 3000', 'lat' => -34.5541, 'lng' => -58.4381],
                ['address' => 'Juramento 2000', 'lat' => -34.5601, 'lng' => -58.4641],
                ['address' => 'Av. del Libertador 6000', 'lat' => -34.5521, 'lng' => -58.4221],
                ['address' => 'Virrey Vértiz 1200', 'lat' => -34.5681, 'lng' => -58.4521],
            ],
            'Microcentro' => [
                ['address' => 'Av. Corrientes 800', 'lat' => -34.6031, 'lng' => -58.3781],
                ['address' => 'Florida 600', 'lat' => -34.6011, 'lng' => -58.3751],
                ['address' => 'Lavalle 700', 'lat' => -34.6021, 'lng' => -58.3791],
                ['address' => 'Av. de Mayo 900', 'lat' => -34.6091, 'lng' => -58.3731],
                ['address' => 'San Martín 400', 'lat' => -34.6051, 'lng' => -58.3771],
            ],
            'Barracas' => [
                ['address' => 'Av. Montes de Oca 1200', 'lat' => -34.6351, 'lng' => -58.3691],
                ['address' => 'Av. Patricios 800', 'lat' => -34.6311, 'lng' => -58.3841],
                ['address' => 'California 1500', 'lat' => -34.6291, 'lng' => -58.3821],
                ['address' => 'Brandsen 900', 'lat' => -34.6331, 'lng' => -58.3801],
            ],
            'La Boca' => [
                ['address' => 'Caminito 100', 'lat' => -34.6371, 'lng' => -58.3641],
                ['address' => 'Av. Pedro de Mendoza 1800', 'lat' => -34.6391, 'lng' => -58.3621],
                ['address' => 'Necochea 1100', 'lat' => -34.6351, 'lng' => -58.3661],
                ['address' => 'Brandsen 500', 'lat' => -34.6311, 'lng' => -58.3681],
            ],
            'Flores' => [
                ['address' => 'Av. Rivadavia 6800', 'lat' => -34.6281, 'lng' => -58.4641],
                ['address' => 'Av. Directorio 3200', 'lat' => -34.6241, 'lng' => -58.4681],
                ['address' => 'Av. Nazca 3000', 'lat' => -34.6301, 'lng' => -58.4721],
                ['address' => 'Boyacá 2200', 'lat' => -34.6261, 'lng' => -58.4701],
            ],
            'Almagro' => [
                ['address' => 'Av. Corrientes 3800', 'lat' => -34.6041, 'lng' => -58.4181],
                ['address' => 'Av. Medrano 900', 'lat' => -34.6021, 'lng' => -58.4221],
                ['address' => 'Castro Barros 1200', 'lat' => -34.6061, 'lng' => -58.4161],
                ['address' => 'Sarmiento 3600', 'lat' => -34.6001, 'lng' => -58.4141],
            ],
            'Once' => [
                ['address' => 'Av. Pueyrredón 200', 'lat' => -34.6081, 'lng' => -58.4021],
                ['address' => 'Av. Corrientes 2500', 'lat' => -34.6041, 'lng' => -58.4001],
                ['address' => 'Bartolomé Mitre 2800', 'lat' => -34.6101, 'lng' => -58.3961],
                ['address' => 'Av. Rivadavia 2200', 'lat' => -34.6121, 'lng' => -58.3981],
            ]
        ];

        $neighborhoods = array_keys($businessLocations);
        
        // Distribución de negocios por rol de usuario
        // Customers: 70% de usuarios (700) -> 1 negocio cada 2 usuarios = ~350 negocios
        // Employees: 15% de usuarios (150) -> 1 negocio cada 1 usuario = 150 negocios  
        // Managers: 10% de usuarios (100) -> 2 negocios por usuario = 200 negocios
        // Admins: 5% de usuarios (50) -> No tienen negocios = 0 negocios
        // Total: ~700 negocios

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
        
        // Customers: 50% tienen 1 negocio (350 negocios)
        $selectedCustomers = $faker->randomElements($usersByRole['Customer'], intval(count($usersByRole['Customer']) * 0.5));
        foreach ($selectedCustomers as $userId) {
            $businessesToCreate[] = $userId;
        }

        // Employees: cada uno tiene 1 negocio (150 negocios)
        foreach ($usersByRole['Employee'] as $userId) {
            $businessesToCreate[] = $userId;
        }

        // Managers: cada uno tiene 2 negocios (200 negocios)
        foreach ($usersByRole['Manager'] as $userId) {
            $businessesToCreate[] = $userId;
            $businessesToCreate[] = $userId; // Segundo negocio
        }

        // Mezclar para distribución aleatoria
        shuffle($businessesToCreate);
        
        $totalBusinesses = count($businessesToCreate);
        
        // Crear negocios distribuidos entre usuarios del seeder argentino
        $this->command->info("Creando {$totalBusinesses} negocios en CABA...");
        $this->command->info('Distribución: ~350 Customers, 150 Employees, 200 Managers');
        $bar = $this->command->getOutput()->createProgressBar($totalBusinesses);
        $bar->start();

        for ($i = 0; $i < $totalBusinesses; $i++) {
            try {
                $categoryId = $faker->randomElement($categories);
                $category = Category::find($categoryId);
                $categoryName = $category->category_name;
                
                // Obtener nombres según la categoría
                $availableNames = $businessNames[$categoryName] ?? ['Restaurant Genérico', 'Comida & Sabor', 'Gastronomía BA'];
                $businessName = $faker->randomElement($availableNames);
                
                // Agregar variación al nombre para evitar duplicados
                if ($faker->boolean(30)) {
                    $neighborhood = $faker->randomElement($neighborhoods);
                    $businessName .= ' - ' . $neighborhood;
                }
                
                // Seleccionar ubicación aleatoria
                $neighborhood = $faker->randomElement($neighborhoods);
                $location = $faker->randomElement($businessLocations[$neighborhood]);
                
                // Código postal de CABA
                $zipCodes = ['C1000AAA', 'C1001AAA', 'C1002AAA', 'C1003AAA', 'C1004AAA', 'C1005AAA'];
                
                // Crear el negocio
                $business = Business::create([
                    'business_uuid' => (string) Str::uuid(),
                    'business_logo' => $faker->boolean(70) ? 'https://foodly.s3.amazonaws.com/public/business_logos/logo' . $faker->numberBetween(1, 100) . '.jpg' : null,
                    'business_name' => $businessName,
                    'business_email' => strtolower(str_replace([' ', '-'], ['', ''], $businessName)) . '@business.com',
                    'business_phone' => '+54 011 ' . $faker->numberBetween(1000, 9999) . '-' . $faker->numberBetween(1000, 9999),
                    'business_address' => $location['address'],
                    'business_zipcode' => $faker->randomElement($zipCodes),
                    'business_city' => $neighborhood . ', Ciudad Autónoma de Buenos Aires',
                    'business_country' => 'Argentina',
                    'business_website' => $faker->boolean(40) ? 'www.' . strtolower(str_replace([' ', '-'], ['', ''], $businessName)) . '.com' : null,
                    'business_about_us' => $this->generateBusinessDescription($categoryName, $faker),
                    'business_additional_info' => $faker->boolean(30) ? 'Aceptamos todas las tarjetas de crédito. Estacionamiento disponible.' : null,
                    'business_latitude' => $location['lat'],
                    'business_longitude' => $location['lng'],
                    'category_id' => $categoryId,
                    'user_id' => $businessesToCreate[$i], // Usuario específico según distribución
                ]);

                // Crear horarios comerciales
                $this->createBusinessHours($business->id, $faker);

                // Asignar servicios aleatorios (2-6 servicios por negocio)
                $randomServices = $faker->randomElements($services, $faker->numberBetween(2, 6));
                $business->services()->attach($randomServices);

                // Crear imágenes de portada (1-4 imágenes)
                $this->createCoverImages($business->id, $faker);

                // Crear promociones ocasionalmente (30% de probabilidad)
                if ($faker->boolean(30)) {
                    $this->createPromotion($business->id, $faker);
                }

            } catch (\Exception $e) {
                $this->command->error("Error creando negocio {$i}: " . $e->getMessage());
                continue;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("¡{$totalBusinesses} negocios creados exitosamente en CABA!");
        $this->command->info('Distribución final:');
        $this->command->info('- ~350 negocios de Customers (50% de customers tienen 1 negocio)');
        $this->command->info('- 150 negocios de Employees (100% tienen 1 negocio)');  
        $this->command->info('- 200 negocios de Managers (100% tienen 2 negocios)');
        $this->command->info('- Distribuidos en 12 barrios de Buenos Aires');
        $this->command->info('- Con horarios, servicios, imágenes y promociones');
    }

    private function generateBusinessDescription($categoryName, $faker)
    {
        $descriptions = [
            'Cocina Internacional' => 'Restaurante de cocina internacional con platos de todo el mundo. Ambiente acogedor y carta variada.',
            'Comida Rápida' => 'Local de comida rápida con delivery y take away. Hamburguesas, papas fritas y bebidas.',
            'Pizzerías' => 'Pizzería tradicional con masa casera y ingredientes frescos. Especialidad en pizzas a la piedra.',
            'Cocina Japonesa' => 'Restaurante japonés auténtico. Sushi, sashimi y platos calientes tradicionales.',
            'Carnes y Parrillas' => 'Parrilla argentina tradicional. Carnes premium a la parrilla y vinos selectos.',
            'Fusión' => 'Cocina de autor con técnicas modernas. Experiencia gastronómica única.',
            'Cocina Vegetariana' => 'Restaurante vegetariano y vegano. Ingredientes orgánicos y platos saludables.',
            'Cocina Mexicana' => 'Auténtica comida mexicana. Tacos, quesadillas y especialidades picantes.',
            'Cocina Koreana' => 'Restaurante coreano tradicional. BBQ coreano y platos típicos de Corea.',
            'Cocina Portuguesa' => 'Cocina portuguesa casera. Bacalao, pasteles de nata y vinos de Porto.',
            'Pastelería y Postres' => 'Pastelería artesanal. Tortas, pasteles y café de especialidad.',
            'Pubs y Vinerías' => 'Pub con ambiente relajado. Cervezas artesanales y vinos de bodega.',
            'Cafés y Desayunos' => 'Cafetería tradicional porteña. Desayunos completos y café de especialidad.',
            'Mercados y Tiendas' => 'Tienda gourmet con productos selectos. Quesos, fiambres y delicatessen.',
            'Escuelas de Cocina' => 'Escuela de cocina profesional. Cursos y talleres gastronómicos.'
        ];

        return $descriptions[$categoryName] ?? 'Excelente gastronomía en el corazón de Buenos Aires. Ambiente familiar y precios accesibles.';
    }

    private function createBusinessHours($businessId, $faker)
    {
        // Horarios típicos para diferentes días
        $schedules = [
            'restaurant' => [
                ['open_a' => '12:00', 'close_a' => '15:30', 'open_b' => '20:00', 'close_b' => '23:30'],
                ['open_a' => '11:30', 'close_a' => '24:00', 'open_b' => null, 'close_b' => null],
            ],
            'cafe' => [
                ['open_a' => '08:00', 'close_a' => '20:00', 'open_b' => null, 'close_b' => null],
                ['open_a' => '07:30', 'close_a' => '22:00', 'open_b' => null, 'close_b' => null],
            ],
            'fast_food' => [
                ['open_a' => '11:00', 'close_a' => '02:00', 'open_b' => null, 'close_b' => null],
                ['open_a' => '10:00', 'close_a' => '24:00', 'open_b' => null, 'close_b' => null],
            ]
        ];

        $schedule = $faker->randomElement($schedules['restaurant']);

        for ($day = 0; $day < 7; $day++) {
            // Algunos negocios cierran los lunes
            if ($day == 1 && $faker->boolean(20)) {
                BusinessHour::create([
                    'business_id' => $businessId,
                    'day' => $day,
                    'open_a' => null,
                    'close_a' => null,
                    'open_b' => null,
                    'close_b' => null,
                ]);
            } else {
                // Viernes y sábados pueden tener horarios extendidos
                $extendedHours = ($day >= 5 && $day <= 6) && $faker->boolean(60);
                
                BusinessHour::create([
                    'business_id' => $businessId,
                    'day' => $day,
                    'open_a' => $schedule['open_a'],
                    'close_a' => $extendedHours ? '01:00' : $schedule['close_a'],
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
            'Happy Hour 2x1', 'Descuento 20%', 'Menú Degustación', 'Promo Almuerzo',
            'Descuento Estudiantes', '2x1 en Pizzas', 'Noche de Vinos', 'Menú Ejecutivo'
        ];

        $promoDescriptions = [
            'De lunes a viernes de 15 a 18hs. ¡No te lo pierdas!',
            'Descuento especial todos los martes y jueves.',
            'Menú especial con entrada, plato principal y postre.',
            'Promoción válida de 12 a 16hs de lunes a viernes.',
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