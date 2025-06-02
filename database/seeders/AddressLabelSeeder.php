<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AddressLabel;
use Illuminate\Support\Str;

class AddressLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Home',
                'description' => 'Casa o residencia principal',
                'icon' => 'home',
                'is_active' => true,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Work',
                'description' => 'Lugar de trabajo u oficina',
                'icon' => 'work',
                'is_active' => true,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Other',
                'description' => 'Otra dirección (temporal, familiar, etc.)',
                'icon' => 'location',
                'is_active' => true,
            ],
        ];

        foreach ($labels as $label) {
            AddressLabel::create($label);
        }

        $this->command->info('Address labels seeded successfully!');
    }
} 