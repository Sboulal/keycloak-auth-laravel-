<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\ColisManagment\Database\Seeders\ColisManagementTestSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\CitiesSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\DeliveryTypesSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\RegionTypePricingSeederTableSeeder;

class ColisManagmentDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
     public function run()
    {
        $this->call([
            // ColisManagementTestSeederTableSeeder::class,
            CitiesSeederTableSeeder::class,
            DeliveryTypesSeederTableSeeder::class,
            // RegionTypePricingSeederTableSeeder::class,
        ]);
    }
}
