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
      Schema::create('drivers', function (Blueprint $table) {
    $table->id();
    $table->string('code_driver')->nullable();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
    $table->string('vehicle_type')->nullable();
    $table->string('vehicle_number')->nullable();
    $table->boolean('is_active')->default(true);
    $table->float('rating')->nullable();
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
        Schema::dropIfExists('drivers');
    }
};
