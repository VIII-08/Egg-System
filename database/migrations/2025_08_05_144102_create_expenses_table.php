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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id(); // Unique ID for the expense record

        // Foreign key to link to the user who recorded the expense
        $table->foreignId('user_id')->constrained('users');

        $table->string('description'); // e.g., "Chicken Feed (2 sacks)", "Fuel for delivery truck"
        $table->decimal('amount', 10, 2); // The cost of the expense
        $table->date('expense_date'); // The date the expense was incurred
        $table->timestamps(); // `created_at` and `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
