<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->after('email_verified_at')
                  ->comment('0: pending, 1: active, 2: suspended');
            $table->boolean('terms_accepted')->default(false)->after('status');
            $table->timestamp('terms_accepted_at')->nullable()->after('terms_accepted');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'terms_accepted', 'terms_accepted_at']);
        });
    }
};