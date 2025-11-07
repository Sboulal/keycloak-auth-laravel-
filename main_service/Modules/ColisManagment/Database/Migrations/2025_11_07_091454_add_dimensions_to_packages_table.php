<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'weight')) {
                $table->float('weight')->nullable()->after('code');
            }
            if (!Schema::hasColumn('packages', 'length')) {
                $table->float('length')->nullable()->after('weight');
            }
            if (!Schema::hasColumn('packages', 'width')) {
                $table->float('width')->nullable()->after('length');
            }
            if (!Schema::hasColumn('packages', 'height')) {
                $table->float('height')->nullable()->after('width');
            }
            if (!Schema::hasColumn('packages', 'content_type')) {
                $table->string('content_type')->nullable()->after('height');
            }
            if (!Schema::hasColumn('packages', 'description')) {
                $table->text('description')->nullable()->after('content_type');
            }
            if (!Schema::hasColumn('packages', 'declared_value')) {
                $table->float('declared_value')->nullable()->after('description');
            }
        });
    }

    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'weight',
                'length',
                'width',
                'height',
                'content_type',
                'description',
                'declared_value',
            ]);
        });
    }
};
