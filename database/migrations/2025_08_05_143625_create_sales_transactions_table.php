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
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id(); // Unique ID for this transaction

        // This creates a column 'user_id' and sets up a foreign key relationship
        // It links to the 'id' column on the 'users' table
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

        $table->decimal('total_amount', 10, 2); // The final total price for the entire transaction
        $table->timestamps(); // `created_at` will be the transaction date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
