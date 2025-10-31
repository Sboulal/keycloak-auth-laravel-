<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('livreurs', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->decimal('start_lat', 10, 7);
        $table->decimal('start_lng', 10, 7);
        $table->decimal('end_lat', 10, 7);
        $table->decimal('end_lng', 10, 7);
        $table->string('color', 7)->default('#FF4444');
        $table->boolean('active')->default(true);
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livreurs');
    }
};
