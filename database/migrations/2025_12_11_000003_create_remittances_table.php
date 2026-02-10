<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketing_user_id')->constrained('users');
            $table->foreignId('treasurer_user_id')->nullable()->constrained('users');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'received'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};











