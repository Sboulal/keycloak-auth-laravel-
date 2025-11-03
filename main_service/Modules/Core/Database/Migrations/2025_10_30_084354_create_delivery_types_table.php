<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{ 
    public function up()
    {
        Schema::create('delivery_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code_dt');
            $table->longText('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('delivery_types');
    }
};
