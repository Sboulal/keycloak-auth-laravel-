<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
   public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('code_driver');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('center_id')->constrained('centers')->onDelete('cascade');
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->boolean('is_actif')->default(true);
            $table->float('rating')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};
