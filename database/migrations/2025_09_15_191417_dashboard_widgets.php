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
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('dashboard_id');
            $table->integer('widget_type_id');
            $table->string('name');
            $table->integer('order')->nullable();
            $table->json('metadata');
            $table->timestamps();
        });
    }
    /**
     * metadata array contents:
     * map_filename
     * y-axis
     * x-axis
     * mapLinkID: stores the widget ID of the map we point to
     * colorMap[$label] => color hex code
     * widgetData[] stores misc widget data
     *      - widgetData['mapColorRules'][rule#] => $rule
     *      - widgetData['colorGradients'][$gradientID] => [] of hex codes
     */

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
