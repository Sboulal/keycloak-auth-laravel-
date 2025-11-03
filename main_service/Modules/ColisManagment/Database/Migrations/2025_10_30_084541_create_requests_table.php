<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('code_request');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('center_id')->constrained('centers')->onDelete('cascade');
            $table->string('receiver_address');
            $table->float('receiver_latitude');
            $table->float('receiver_longitude');
            $table->string('receiver_phone');
            $table->boolean('status')->default(false);
            $table->string('payment_status')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->decimal('amount', 10, 2)->nullable();
            $table->foreignId('delivery_type_id')->constrained('delivery_types')->onDelete('cascade');
            $table->timestamp('estimated_time')->nullable();
            $table->string('qr_code_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('requests');
    }
};
