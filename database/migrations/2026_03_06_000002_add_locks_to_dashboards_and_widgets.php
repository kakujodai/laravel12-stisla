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
        Schema::table('dashboards', function (Blueprint $table) {
            if (!Schema::hasColumn('dashboards', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('name');
            }
        });

        Schema::table('dashboard_widgets', function (Blueprint $table) {
            if (!Schema::hasColumn('dashboard_widgets', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->after('layout_column');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table) {
            if (Schema::hasColumn('dashboard_widgets', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });

        Schema::table('dashboards', function (Blueprint $table) {
            if (Schema::hasColumn('dashboards', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
