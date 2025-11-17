<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Entities\Center;
use Modules\Core\Entities\City;

class CentersSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // Get city (make sure city are seeded first)
        $casablanca = City::where('name', 'Casablanca')->first();
        $rabat = City::where('name', 'Rabat')->first();
        $marrakech = City::where('name', 'Marrakech')->first();
        $tanger = City::where('name', 'Tanger')->first();
        $fes = City::where('name', 'Fès')->first();

        $centers = [
            // Casablanca Centers
            [
                'code_center' => 'CAS-MAA-001',
                'name' => 'Centre Maarif',
                'city_id' => $casablanca?->id ?? 1,
                'address' => 'Boulevard Zerktouni, Maarif',
                'phone' => '+212522987654',
                 
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'is_active' => true,
            ],
            [
                'code_center' => 'CAS-AIN-002',
                'name' => 'Centre Ain Diab',
                'city_id' => $casablanca?->id ?? 1,
                'address' => 'Corniche Ain Diab',
                'phone' => '+212522876543',
                 
                'latitude' => 33.6062,
                'longitude' => -7.6608,
                'is_active' => true,
            ],
            [
                'code_center' => 'CAS-DER-003',
                'name' => 'Centre Derb Sultan',
                'city_id' => $casablanca?->id ?? 1,
                'address' => 'Rue Chaouia, Derb Sultan',
                'phone' => '+212522765432',
                
                'latitude' => 33.5892,
                'longitude' => -7.6031,
                'is_active' => true,
            ],

            // Rabat Centers
            [
                'code_center' => 'RAB-AGD-001',
                'name' => 'Centre Agdal',
                'city_id' => $rabat?->id ?? 2,
                'address' => 'Avenue France, Agdal',
                'phone' => '+212537654321',
                 
                'latitude' => 33.9716,
                'longitude' => -6.8498,
                'is_active' => true,
            ],
            [
                'code_center' => 'RAB-HAS-002',
                'name' => 'Centre Hassan',
                'city_id' => $rabat?->id ?? 2,
                'address' => 'Avenue Hassan II',
                'phone' => '+212537543210',
               
                'latitude' => 34.0209,
                'longitude' => -6.8416,
                'is_active' => true,
            ],

            // Marrakech Centers
            [
                'code_center' => 'MAR-GUE-001',
                'name' => 'Centre Guéliz',
                'city_id' => $marrakech?->id ?? 3,
                'address' => 'Avenue Mohammed V, Guéliz',
                'phone' => '+212524432109',
                
                'latitude' => 31.6295,
                'longitude' => -7.9811,
                'is_active' => true,
            ],
            [
                'code_center' => 'MAR-MED-002',
                'name' => 'Centre Medina',
                'city_id' => $marrakech?->id ?? 3,
                'address' => 'Place Jemaa el-Fna',
                'phone' => '+212524321098',
                 
                'latitude' => 31.6258,
                'longitude' => -7.9890,
                'is_active' => true,
            ],

            // Tanger Centers
            [
                'code_center' => 'TAN-VIL-001',
                'name' => 'Centre Ville',
                'city_id' => $tanger?->id ?? 4,
                'address' => 'Boulevard Pasteur',
                'phone' => '+212539210987',
                
                'latitude' => 35.7595,
                'longitude' => -5.8340,
                'is_active' => true,
            ],

            // Fès Centers
            [
                'code_center' => 'FES-VNO-001',
                'name' => 'Centre Fès Ville Nouvelle',
                'city_id' => $fes?->id ?? 5,
                'address' => 'Avenue Hassan II',
                'phone' => '+212535109876',
                
                'latitude' => 34.0181,
                'longitude' => -5.0078,
                'is_active' => true,
            ],
        ];

        foreach ($centers as $center) {
            Center::updateOrCreate(
                ['code_center' => $center['code_center']],
                $center
            );
        }

        $this->command->info('Centers seeded successfully!');
        $this->command->info('Total centers created: ' . count($centers));
    }
}