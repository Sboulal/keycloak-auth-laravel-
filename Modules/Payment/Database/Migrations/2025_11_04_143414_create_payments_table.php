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
       Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('payment_code')->nullable();
    $table->string('method')->nullable();
    $table->float('amount')->nullable();
    $table->string('currency')->nullable();
    $table->string('status')->nullable();
    $table->json('payment_details')->nullable();
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
       Schema::table('payments', function (Blueprint $table) {
    $table->dropForeign(['request_id']);
});
Schema::dropIfExists('payments');
    }
};
