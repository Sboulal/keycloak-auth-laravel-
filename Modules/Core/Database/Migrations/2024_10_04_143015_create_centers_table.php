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
        Schema::create('centers', function (Blueprint $table) {
    $table->id();
    $table->string('code_center');
    $table->string('name');
    $table->string('address');
    $table->foreignId('city_id')->constrained('city')->cascadeOnDelete();
    $table->string('postal_code')->nullable();
    $table->float('latitude')->nullable();
    $table->float('longitude')->nullable();
    $table->string('phone')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('centers');
    }
};
