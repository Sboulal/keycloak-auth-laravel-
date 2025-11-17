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
       Schema::create('requests', function (Blueprint $table) {
    $table->id();

    $table->string('code')->nullable();

    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('center_id')->nullable()->constrained('centers')->nullOnDelete();
    $table->foreignId('city_id')->nullable()->constrained('city')->nullOnDelete();
    $table->foreignId('delivery_type_id')->nullable()->constrained('delivery_types')->nullOnDelete();

    $table->string('nom')->nullable();
    $table->string('prenom')->nullable();
    $table->string('telephone')->nullable();
    $table->string('adresse')->nullable();

    $table->float('latitude')->nullable();
    $table->float('longitude')->nullable();
    $table->float('poids')->nullable();
    $table->float('amount')->nullable();

    $table->integer('status')->default(0);          // 0 pending | 1 validée | 2 annulée
    $table->integer('payment_status')->default(0);  // 0 impayée | 1 payée | 2 remboursée

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
        Schema::dropIfExists('requests','cascade');
    }
};
