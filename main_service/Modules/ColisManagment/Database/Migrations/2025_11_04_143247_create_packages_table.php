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
        Schema::table('packages', function (Blueprint $table) {
            $table->float('distance')->nullable()->after('recipient_longitude');
            $table->text('notes')->nullable()->after('distance');
            $table->string('payment_method')->nullable()->after('notes');
            $table->string('source')->default('online')->after('payment_method');
        });    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'recipient_name', 'recipient_phone', 'recipient_address',
                'recipient_city_id', 'recipient_latitude', 'recipient_longitude',
                'distance', 'notes', 'payment_method', 'source'
            ]);
        });
    }
};
