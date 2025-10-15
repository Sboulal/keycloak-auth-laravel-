<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sub')->unique()->nullable()->after('id');
            $table->boolean('email_verified')->default(false)->after('email');
            $table->string('preferred_username')->nullable()->after('email_verified');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sub', 'email_verified', 'preferred_username']);
        });
    }
};
