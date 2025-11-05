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
       Schema::create('packages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
    $table->string('code_pkg')->nullable();
    $table->string('tracking_number')->nullable();
    $table->float('weight')->nullable();
    $table->float('length')->nullable();
    $table->float('height')->nullable();
    $table->string('content_type')->nullable();
    $table->text('description')->nullable();
    $table->float('declared_value')->nullable();
    $table->string('status')->nullable();
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
        Schema::dropIfExists('packages');
    }
};
