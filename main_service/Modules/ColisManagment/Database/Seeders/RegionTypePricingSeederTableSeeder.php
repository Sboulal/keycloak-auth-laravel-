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
        $pricing = [
            'Casablanca' => ['standard' => [15, 3], 'express' => [25, 5]],
            'Rabat' => ['standard' => [20, 4], 'express' => [30, 6]],
            'Fès' => ['standard' => [25, 5], 'express' => [40, 8]],
            'Marrakech' => ['standard' => [25, 5], 'express' => [40, 8]],
            'Tangier' => ['standard' => [30, 6], 'express' => [45, 9]],
            'Agadir' => ['standard' => [30, 6], 'express' => [45, 9]],
            'Meknès' => ['standard' => [20, 4], 'express' => [30, 6]],
            'Oujda' => ['standard' => [35, 7], 'express' => [50, 10]],
            'Kenitra' => ['standard' => [20, 4], 'express' => [30, 6]],
            'Mohammedia' => ['standard' => [10, 2], 'express' => [15, 3]],
        ];

        $standardType = DeliveryType::where('name', 'Standard')->first();
        $expressType = DeliveryType::where('name', 'Express')->first();

        foreach ($pricing as $cityName => $prices) {
            $city = City::where('name', $cityName)->first();

            if ($city) {
                // Standard pricing
               RegionTypePricing::create([
                    'city_id' => $city->id,
                    'delivery_type_id' => $standardType->id,
                    'base_price' => $prices['standard'][0],
                    'price_per_kg' => $prices['standard'][1],
                ]);

                // Express pricing
               RegionTypePricing::create([
                    'city_id' => $city->id,
                    'delivery_type_id' => $expressType->id,
                    'base_price' => $prices['express'][0],
                    'price_per_kg' => $prices['express'][1],
                ]);
            }
        }
    }
}
