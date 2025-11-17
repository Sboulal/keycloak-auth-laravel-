<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\City;
use Illuminate\Database\Eloquent\Model;
use Modules\ColisManagment\Entities\DeliveryType;
use Modules\ColisManagment\Entities\RegionTypePricing;

class RegionTypePricingSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Format: [base_price, price_per_km, price_per_kg]
        $pricing = [
            'Casablanca' => ['standard' => [15, 2, 3], 'express' => [25, 3.5, 5]],
            'Rabat' => ['standard' => [20, 2.5, 4], 'express' => [30, 4, 6]],
            'Fès' => ['standard' => [25, 3, 5], 'express' => [40, 5, 8]],
            'Marrakech' => ['standard' => [25, 3, 5], 'express' => [40, 5, 8]],
            'Tangier' => ['standard' => [30, 3.5, 6], 'express' => [45, 5.5, 9]],
            'Agadir' => ['standard' => [30, 3.5, 6], 'express' => [45, 5.5, 9]],
            'Meknès' => ['standard' => [20, 2.5, 4], 'express' => [30, 4, 6]],
            'Oujda' => ['standard' => [35, 4, 7], 'express' => [50, 6, 10]],
            'Kenitra' => ['standard' => [20, 2.5, 4], 'express' => [30, 4, 6]],
            'Mohammedia' => ['standard' => [10, 1.5, 2], 'express' => [15, 2.5, 3]],
        ];

        $standardType = DeliveryType::where('name', 'Standard')->first();
        $expressType = DeliveryType::where('name', 'Express')->first();

        if (!$standardType || !$expressType) {
            $this->command->error('Les types de livraison (Standard/Express) n\'existent pas. Veuillez exécuter le seeder DeliveryType d\'abord.');
            return;
        }

        // Supprimer les anciennes données pour éviter les doublons
        RegionTypePricing::truncate();

        foreach ($pricing as $cityName => $prices) {
            $city = City::where('name', $cityName)->first();

            if ($city) {
                // Standard pricing
                RegionTypePricing::create([
                    'city_id' => $city->id,
                    'delivery_type_id' => $standardType->id,
                    'base_price' => $prices['standard'][0],
                    'price_per_km' => $prices['standard'][1],
                    'price_per_kg' => $prices['standard'][2],
                    'is_active' => true,
                ]);

                // Express pricing
                RegionTypePricing::create([
                    'city_id' => $city->id,
                    'delivery_type_id' => $expressType->id,
                    'base_price' => $prices['express'][0],
                    'price_per_km' => $prices['express'][1],
                    'price_per_kg' => $prices['express'][2],
                    'is_active' => true,
                ]);

                $this->command->info("Tarification créée pour {$cityName}");
            } else {
                $this->command->warn("Ville non trouvée: {$cityName}");
            }
        }

        $this->command->info('Tarifications créées avec succès !');
    }
}