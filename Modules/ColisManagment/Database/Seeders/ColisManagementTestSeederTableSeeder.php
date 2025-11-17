<?php

namespace Modules\ColisManagment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\UserManagment\Entities\User;

class ColisManagementTestSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Créer un utilisateur de test (sans email)
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'first_name' => 'Mohamed',
                'last_name' => 'Test',
                'phone' => '0612345678',
                'password' => bcrypt('password123'),
                'address' => 'Rue test, Casablanca',
                'city' => 'Casablanca',
                'postal_code' => '20000'
            ]);
            echo "✅ User created with ID: {$user->id}\n";
        } else {
            echo "✅ User already exists with ID: {$user->id}\n";
        }

        // 2. Créer les villes
        $city = [
            ['id' => 1, 'name' => 'Casablanca', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Rabat', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Marrakech', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Tanger', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Fès', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($city as $city) {
            DB::table('city')->updateOrInsert(['id' => $city['id']], $city);
        }

        echo "✅ city created: " . count($city) . "\n";

        // 3. Créer les centres (avec code_center)
        $centers = [
            [
                'id' => 1, 
                'code_center' => 'CTR-MAARIF-001',
                'name' => 'Centre Maarif', 
                'city_id' => 1, 
                'address' => 'Boulevard Maarif, Casablanca', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => 2, 
                'code_center' => 'CTR-AINDIAB-002',
                'name' => 'Centre Ain Diab', 
                'city_id' => 1, 
                'address' => 'Corniche Ain Diab, Casablanca', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => 3, 
                'code_center' => 'CTR-AGDAL-003',
                'name' => 'Centre Agdal', 
                'city_id' => 2, 
                'address' => 'Avenue Agdal, Rabat', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => 4, 
                'code_center' => 'CTR-HASSAN-004',
                'name' => 'Centre Hassan', 
                'city_id' => 2, 
                'address' => 'Avenue Hassan II, Rabat', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
            [
                'id' => 5, 
                'code_center' => 'CTR-GUELIZ-005',
                'name' => 'Centre Guéliz', 
                'city_id' => 3, 
                'address' => 'Avenue Mohammed V, Marrakech', 
                'created_at' => now(), 
                'updated_at' => now()
            ],
        ];

        foreach ($centers as $center) {
            DB::table('centers')->updateOrInsert(['id' => $center['id']], $center);
        }

        echo "✅ Centers created: " . count($centers) . "\n";

        // 4. Créer les types de livraison
        $deliveryTypes = [
            ['id' => 1, 'name' => 'Standard',  'description' => 'Livraison en 3-5 jours ouvrables', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Express',  'description' => 'Livraison en 24h', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Premium',  'description' => 'Livraison le jour même', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Economy',  'description' => 'Livraison en 7-10 jours', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($deliveryTypes as $type) {
            DB::table('delivery_types')->updateOrInsert(['id' => $type['id']], $type);
        }

        echo "✅ Delivery types created: " . count($deliveryTypes) . "\n";

        echo "\n🎉 Toutes les données de test ont été créées avec succès!\n";
    }
}