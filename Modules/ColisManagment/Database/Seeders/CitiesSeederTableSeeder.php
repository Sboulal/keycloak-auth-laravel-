<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\City;
use Illuminate\Database\Eloquent\Model;

class CitiesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
{
    $city = [
        ['name' => 'Casablanca'],
        ['name' => 'Rabat'],
        ['name' => 'Fès'],
        ['name' => 'Marrakech'],
        ['name' => 'Tangier'],
        ['name' => 'Agadir'],
        ['name' => 'Meknès'],
        ['name' => 'Oujda'],
        ['name' => 'Kenitra'],
        ['name' => 'Mohammedia'],
    ];

   foreach ($city as $data) {
    City::updateOrCreate(['name' => $data['name']], $data);
}

    }
}