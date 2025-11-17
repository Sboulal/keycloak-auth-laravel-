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
       Schema::create('deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
    $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
    $table->string('pickup_address')->nullable();
    $table->float('pickup_latitude')->nullable();
    $table->float('pickup_longitude')->nullable();
    $table->string('destination_address')->nullable();
    $table->float('destination_latitude')->nullable();
    $table->float('destination_longitude')->nullable();
    $table->boolean('status')->default(false);
    $table->text('rejection_reason')->nullable();
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
        Schema::dropIfExists('deliveries');
    }
};
