<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\ColisManagment\Entities\DeliveryType;


class DeliveryTypesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
  public function run()
    {
        $types = [
            [
                'name' => 'Standard',
                
                'description' => 'Livraison standard (24-48h)',
                
            ],
            [
                'name' => 'Express',
                
                'description' => 'Livraison express (12-24h)',
                
            ],
        ];

       foreach ($types as $data) {
    DeliveryType::updateOrCreate(['name' => $data['name']], $data);
}
    }
}
