<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('city', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code_city');
            $table->float('latitude');
            $table->float('longitude');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('city');
    }
};
