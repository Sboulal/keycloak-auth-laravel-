<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
public function up()
    {
        Schema::create('region_type_pricings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('city')->cascadeOnDelete();
            $table->foreignId('delivery_type_id')->constrained('delivery_types')->cascadeOnDelete();
            $table->float('base_price')->default(0);
            $table->float('price_per_km')->default(0);
            $table->float('price_per_kg')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('region_type_pricings');
    }
};
