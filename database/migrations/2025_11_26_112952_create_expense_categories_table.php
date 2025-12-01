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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('role', ['staff-production', 'staff-marketing']);
            $table->timestamps();
            
            // Ensure unique category name per role
            $table->unique(['name', 'role']);
        });

        // Seed initial categories
        $productionCategories = [
            'Feeds', 'Biologics', 'Miscellaneous(tray)', 'Electricity',
            'Water', 'Repairs(building)', 'Fuel', 'Transportation'
        ];

        $marketingCategories = [
            'Fuel', 'Transportation'
        ];

        foreach ($productionCategories as $category) {
            DB::table('expense_categories')->insert([
                'name' => $category,
                'role' => 'staff-production',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($marketingCategories as $category) {
            DB::table('expense_categories')->insert([
                'name' => $category,
                'role' => 'staff-marketing',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
