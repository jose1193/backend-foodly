<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class CategorySeeder extends Seeder
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

        $categories = [
            [
                'category_name' => 'Cocina Internacional',
                'category_description' => 'Descripción de Cocina Internacional',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/oMAAZvmiXpRoIXhPMUgan7o4m6GS0VngGzuExxF4.jpg'
            ],
            [
                'category_name' => 'Comida Rápida',
                'category_description' => 'Descripción de Comida Rápida',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/3R7zsx7VXzFekjM2qFz0pktxpYeMnhT6b7fKmTo5.jpg'
            ],
            [
                'category_name' => 'Pizzerías',
                'category_description' => 'Descripción de Pizzerías',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/zy66xbwjltRgitojWuzbUoOxX4OpLfQUmlotimVi.jpg'
            ],
            [
                'category_name' => 'Cocina Japonesa',
                'category_description' => 'Descripción de Cocina Japonesa',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/rEvbOnKbTYnpoz7Qc1Vu0jHXSDt8YMer2WaxqyOz.jpg'
            ],
            [
                'category_name' => 'Carnes y Parrillas',
                'category_description' => 'Descripción de Carnes y Parrillas',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/DXj6PxsVPe9inTs8eDh2u47b1LhgwOpOXyYNV7Gp.jpg'
            ],
            [
                'category_name' => 'Fusión',
                'category_description' => 'Descripción de Fusión',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/6uT3gxrcugQPiMIxICRVG06cHQbYBOCnPKolRqIh.jpg'
            ],
            [
                'category_name' => 'Cocina Vegetariana',
                'category_description' => 'Descripción de Cocina Vegetariana',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/BpIS39yEA6lpcIRjSbkWC3Tt4lgrp2Pk4NclnMKn.jpg'
            ],
            [
                'category_name' => 'Cocina Mexicana',
                'category_description' => 'Descripción de Cocina Mexicana',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/6rvuMCOXDbQm08Xf5D2snlnBj5sfTIcMF5PeLtcB.jpg'
            ],
            [
                'category_name' => 'Cocina Koreana',
                'category_description' => 'Descripción de Cocina Koreana',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/FoJ3Uycii6zgFHelMfU6rR5w0A0PHnjcKv8jvMuy.jpg'
            ],
            [
                'category_name' => 'Cocina Portuguesa',
                'category_description' => 'Descripción de Cocina Portuguesa',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/OKZVzDcXq0csGYOsBZiSPa16oMrovNzQBr8sYmAT.jpg'
            ],
            [
                'category_name' => 'Pastelería y Postres',
                'category_description' => 'Descripción de Pastelería y Postres',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/eo6lgLdLH87PCLbwjyNVTTuNeXzxW4Jx67EGNDaS.jpg'
            ],
            [
                'category_name' => 'Pubs y Vinerías',
                'category_description' => 'Descripción de Pubs y Vinerías',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/31DbV0wNR6topTeaVpBBlD8AdtJrSxD26XgC16hQ.jpg'
            ],
            [
                'category_name' => 'Cafés y Desayunos',
                'category_description' => 'Descripción de Cafés y Desayunos',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/NC2D2vwB28ReT6E9HyBaq1rkJJLGCBodBLdNLvee.jpg'
            ],
            [
                'category_name' => 'Mercados y Tiendas',
                'category_description' => 'Descripción de Mercados y Tiendas',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/9UrhIE91n2kbClChH8d0zAJ3WHdKv4cQ06fqq2NU.jpg'
            ],
            [
                'category_name' => 'Escuelas de Cocina',
                'category_description' => 'Descripción de Escuelas de Cocina',
                'category_image_path' => 'https://foodly.s3.amazonaws.com/public/categories_images/sck0rO2HlaBnXzHMAaYPLuIJhYkTlqmMauOrZZYM.jpg'
            ]
        ];

        foreach ($categories as $category) {
            Category::create([
                'category_uuid' => Uuid::uuid4()->toString(),
                'category_name' => $category['category_name'],
                'category_description' => $category['category_description'],
                'category_image_path' => $category['category_image_path'],
                'user_id' => $firstUser->id
            ]);
        }
    }
}