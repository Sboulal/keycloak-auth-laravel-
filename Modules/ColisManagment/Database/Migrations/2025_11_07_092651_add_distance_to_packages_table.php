<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'distance')) {
                $table->float('distance')->nullable()->after('declared_value');
            }

            if (!Schema::hasColumn('packages', 'notes')) {
                $table->text('notes')->nullable()->after('distance');
            }

            if (!Schema::hasColumn('packages', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('notes');
            }

            if (!Schema::hasColumn('packages', 'source')) {
                $table->string('source')->default('online')->after('payment_method');
            }
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['distance', 'notes', 'payment_method', 'source']);
        });
    }
};
