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
        Schema::create('data_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // The staff member making the request
            $table->string('request_type'); // e.g., 'Egg Production Log', 'Expense Record'
            $table->unsignedBigInteger('reference_id'); // The ID of the specific record to be changed
            $table->text('description_of_error'); // User explains what's wrong
            $table->text('proposed_correction'); // User explains what it should be
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // The admin who reviewed it
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable(); // Admin's comments
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_correction_requests');
    }
};
