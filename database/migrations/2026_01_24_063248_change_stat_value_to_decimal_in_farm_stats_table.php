<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change stat_value from bigInteger to decimal to support feed quantities
        Schema::table('farm_stats', function (Blueprint $table) {
            $table->decimal('stat_value', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farm_stats', function (Blueprint $table) {
            $table->bigInteger('stat_value')->default(0)->change();
        });
    }
};
