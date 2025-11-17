<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Modifier la table drivers pour ajouter les informations de tracking
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->decimal('last_latitude', 10, 7)->nullable()->after('id');
            $table->decimal('last_longitude', 10, 7)->nullable()->after('last_latitude');
            $table->timestamp('last_position_update')->nullable()->after('last_longitude');
            $table->string('vehicle_type')->default('car')->after('last_position_update'); // car, bike, truck
            // $table->string('vehicle_type')->default('car')->after('last_position_update'); // car, bike, truck
            $table->boolean('is_online')->default(false)->after('vehicle_type');
        });

       

       

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
