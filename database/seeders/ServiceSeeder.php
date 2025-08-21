<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener el primer usuario creado (Super Admin)
        $firstUser = \App\Models\User::first();
        
        if (!$firstUser) {
            $this->command->error('No se encontraron usuarios. Ejecuta primero el UserSeeder.');
            return;
        }

        $services = [
            [
                'service_name' => 'Wifi',
                'service_description' => 'Descripción de Wifi',
                'service_image_path' => 'storage/app/public/services_images/wifi.png'
            ],
            [
                'service_name' => 'Multilenguage',
                'service_description' => 'Descripción de Multilenguage',
                'service_image_path' => 'storage/app/public/services_images/multilenguage.png'
            ],
            [
                'service_name' => 'Kid Chairs',
                'service_description' => 'Descripción de Kid Chairs',
                'service_image_path' => 'storage/app/public/services_images/kid_chairs.png'
            ],
            [
                'service_name' => 'Baby Changing St..',
                'service_description' => 'Descripción de Baby Changing St..',
                'service_image_path' => 'storage/app/public/services_images/baby_changing_st.png'
            ],
            [
                'service_name' => 'Outdoor',
                'service_description' => 'Outdoor Seating',
                'service_image_path' => 'storage/app/public/services_images/outdoor.png'
            ],
            [
                'service_name' => 'PMR',
                'service_description' => 'Descripción de PMR',
                'service_image_path' => 'storage/app/public/services_images/pmr.png'
            ],
            [
                'service_name' => 'Kids Play Area',
                'service_description' => 'Descripción de Kids Play Area',
                'service_image_path' => 'storage/app/public/services_images/kid_play.png'
            ],
            [
                'service_name' => 'Delivery',
                'service_description' => 'Descripción de Delivery',
                'service_image_path' => 'storage/app/public/services_images/delivery.png'
            ],
            [
                'service_name' => 'Take Away',
                'service_description' => 'Descripción de Take Away',
                'service_image_path' => 'storage/app/public/services_images/take_away.png'
            ],
            [
                'service_name' => 'Smoking Area',
                'service_description' => 'Descripción de Smoking Area',
                'service_image_path' => 'storage/app/public/services_images/smoking_area.png'
            ],
            [
                'service_name' => 'Happy Hours',
                'service_description' => 'Descripción de Happy Hours',
                'service_image_path' => 'storage/app/public/services_images/happy_hours.png'
            ],
            [
                'service_name' => 'Happy Birthday',
                'service_description' => 'Descripción de Happy Birthday',
                'service_image_path' => 'storage/app/public/services_images/happy_birthday.png'
            ],
            [
                'service_name' => 'Parking',
                'service_description' => 'Descripción de Parking',
                'service_image_path' => 'storage/app/public/services_images/parking.png'
            ],
            [
                'service_name' => 'Pet Friendly',
                'service_description' => 'Descripción de Pet Friendly',
                'service_image_path' => 'storage/app/public/services_images/pet_friendly.png'
            ],
            [
                'service_name' => 'Catering',
                'service_description' => 'Descripción de Catering',
                'service_image_path' => 'storage/app/public/services_images/catering.png'
            ],
            [
                'service_name' => 'Live Music',
                'service_description' => 'Descripción de Live Music',
                'service_image_path' => 'storage/app/public/services_images/livemusic.png'
            ],
            [
                'service_name' => 'On Site',
                'service_description' => 'Descripción de On Site',
                'service_image_path' => 'storage/app/public/services_images/on_site.png'
            ],
            [
                'service_name' => 'Kids Menu',
                'service_description' => 'Descripción de Kids Menu',
                'service_image_path' => 'storage/app/public/services_images/kids_menu.png'
            ]
        ];

        foreach ($services as $service) {
            Service::create([
                'service_uuid' => Uuid::uuid4()->toString(),
                'service_name' => $service['service_name'],
                'service_description' => $service['service_description'],
                'service_image_path' => $service['service_image_path'],
                'user_id' => $firstUser->id
            ]);
        }
    }
}