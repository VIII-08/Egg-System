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
        Schema::create('farm_stats', function (Blueprint $table) {
            $table->id();
            $table->string('stat_key')->unique(); // e.g., 'current_chicken_stock'
            $table->bigInteger('stat_value')->default(0);
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_stats');
    }
};
