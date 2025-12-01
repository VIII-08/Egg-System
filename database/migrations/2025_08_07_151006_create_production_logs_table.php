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
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Who recorded it
            $table->foreignId('egg_product_id')->constrained('egg_products'); // Which egg type
            $table->integer('quantity'); // How many were collected
            $table->date('log_date'); // Date of collection
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
