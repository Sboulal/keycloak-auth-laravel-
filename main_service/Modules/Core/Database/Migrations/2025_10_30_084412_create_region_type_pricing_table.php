<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
   public function up()
    {
        Schema::create('region_type_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained('city')->onDelete('cascade');
            $table->foreignId('delivery_type_id')->constrained('delivery_types')->onDelete('cascade');
            $table->string('basis_price');
            $table->string('price_per_km')->nullable();
            $table->string('price_per_kg')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('region_type_pricing');
    }
};
