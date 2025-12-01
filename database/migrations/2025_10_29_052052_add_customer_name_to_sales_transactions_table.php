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
    Schema::table('sales_transactions', function (Blueprint $table) {
        // Add a nullable string column to store the customer name after the 'total_amount' column
        $table->string('customer_name')->nullable()->after('total_amount');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            //
        });
    }
};
