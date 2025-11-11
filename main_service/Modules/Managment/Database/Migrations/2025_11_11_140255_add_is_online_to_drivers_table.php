<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            // Ajouter la colonne is_online
            $table->boolean('is_online')->default(false)->after('vehicle_type');
            
            // Optionnel: Ajouter aussi les colonnes de position si elles n'existent pas
            if (!Schema::hasColumn('drivers', 'last_latitude')) {
                $table->decimal('last_latitude', 10, 8)->nullable()->after('is_online');
            }
            if (!Schema::hasColumn('drivers', 'last_longitude')) {
                $table->decimal('last_longitude', 11, 8)->nullable()->after('last_latitude');
            }
            if (!Schema::hasColumn('drivers', 'last_position_update')) {
                $table->timestamp('last_position_update')->nullable()->after('last_longitude');
            }
            
            // Index pour améliorer les performances
            $table->index('is_online');
            $table->index(['last_latitude', 'last_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropIndex(['drivers_is_online_index']);
            $table->dropIndex(['drivers_last_latitude_last_longitude_index']);
            
            $table->dropColumn([
                'is_online',
                'last_latitude',
                'last_longitude',
                'last_position_update'
            ]);
        });
    }
};
