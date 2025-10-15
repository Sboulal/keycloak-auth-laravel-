<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Check if columns don't already exist before adding
            if (!Schema::hasColumn('users', 'sub')) {
                $table->string('sub')->unique()->nullable()->after('id');
            }
            if (!Schema::hasColumn('users', 'email_verified')) {
                $table->boolean('email_verified')->default(false)->after('email');
            }
            if (!Schema::hasColumn('users', 'preferred_username')) {
                $table->string('preferred_username')->nullable()->after('email_verified');
            }
            if (!Schema::hasColumn('users', 'given_name')) {
                $table->string('given_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'family_name')) {
                $table->string('family_name')->nullable()->after('given_name');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'sub', 
                'email_verified', 
                'preferred_username',
                'given_name',
                'family_name'
            ]);
        });
    }
};
