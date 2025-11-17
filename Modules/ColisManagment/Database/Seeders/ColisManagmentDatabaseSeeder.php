<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\ColisManagment\Database\Seeders\CitiesSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\CentersSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\DeliveryTypesSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\RegionTypePricingSeederTableSeeder;
use Modules\ColisManagment\Database\Seeders\ColisManagementTestSeederTableSeeder;

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
            RegionTypePricingSeederTableSeeder::class,
            CentersSeederTableSeeder::class,
        ]);
    }
}
