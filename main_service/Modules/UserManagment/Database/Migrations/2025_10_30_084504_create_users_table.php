<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->float('address_latitude')->nullable();
            $table->float('address_longitude')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
