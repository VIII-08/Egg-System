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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('generated_by')->constrained('users'); // The Treasurer who made it
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_revenue', 10, 2);
            $table->decimal('total_expenses', 10, 2);
            $table->decimal('net_income', 10, 2);
            $table->json('report_data'); // Stores the detailed breakdown (expenses, production, etc.)
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
