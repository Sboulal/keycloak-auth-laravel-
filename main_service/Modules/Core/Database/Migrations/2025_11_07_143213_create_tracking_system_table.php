<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
     public function up()
    {
        // Modifier la table drivers pour ajouter les informations de tracking
        Schema::table('drivers', function (Blueprint $table) {
            $table->decimal('last_latitude', 10, 7)->nullable()->after('id');
            $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            $table->timestamp('last_position_update')->nullable()->after('last_longitude');
            // $table->string('vehicle_type')->default('car')->after('last_position_update'); // car, bike, truck
            $table->boolean('is_online')->default(false)->after('vehicle_type');
        });

        // Créer la table tracking_points pour l'historique
        Schema::create('tracking_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade');
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 5, 2)->nullable(); // km/h
            $table->decimal('accuracy', 6, 2)->nullable(); // mètres
            $table->string('battery_level')->nullable(); // pourcentage
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['delivery_id', 'recorded_at']);
            $table->index(['driver_id', 'recorded_at']);
        });

        Schema::dropIfExists('tracking_points');

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'last_latitude',
                'last_longitude',
                'last_position_update',
                'vehicle_type',
                'is_online'
            ]);
        });
    }
};
