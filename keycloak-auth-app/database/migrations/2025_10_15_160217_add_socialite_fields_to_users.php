<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->default('');
            $table->string('google_token')->default('');
            $table->string('google_refresh_token')->default('');

            // If your app allows both password and social logins, you
            // MUST validate that the password is not blank during login.
            // If you do not, an attacker could gain access to an account
            // that uses social login by only knowing the email.
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('google_id');
            $table->dropColumn('google_token');
            $table->dropColumn('google_refresh_token');
            $table->string('password')->nullable(false)->change();
        });
    }
};
