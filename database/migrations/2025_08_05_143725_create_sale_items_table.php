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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            // Link to the sales_transactions table
            $table->foreignId('sales_transaction_id')->constrained('sales_transactions')->onDelete('cascade');
    
            // Link to the egg_products table
            $table->foreignId('egg_product_id')->constrained('egg_products')->onDelete('restrict');
    
            $table->integer('quantity'); // How many units were sold (e.g., 2 trays)
            $table->decimal('price', 8, 2); // The price of the product *at the time of sale*
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
