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
        Schema::create('egg_products', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key (1, 2, 3...)
        $table->string('name'); // e.g., "Medium Eggs (Tray of 30)"
        $table->text('description')->nullable(); // An optional description
        $table->decimal('price', 8, 2); // Price with 2 decimal places
        $table->integer('stock_quantity')->unsigned()->default(0); // Current stock, can't be negative
        $table->timestamps(); // Adds `created_at` and `updated_at` columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_products');
    }
};
