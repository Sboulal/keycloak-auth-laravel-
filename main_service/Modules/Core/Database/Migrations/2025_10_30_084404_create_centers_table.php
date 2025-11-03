<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
   public function up()
    {
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('code_center');
            $table->string('name');
            $table->string('address');
            $table->foreignId('city_id')->constrained('city')->onDelete('cascade');
            $table->string('postal_code');
            $table->float('latitude');
            $table->float('longitude');
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('centers');
    }
};
