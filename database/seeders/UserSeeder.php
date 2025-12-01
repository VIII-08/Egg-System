<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; 
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the Admin User
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@eggsystem.com',
            'role' => 'admin',
            'password' => Hash::make('password')
        ]);

        // Create the treasurer User
        User::create([
            'name' => 'Treasurer',
            'email' => 'treasurer@eggsystem.com',
            'role' => 'treasurer',
            'password' => Hash::make('password')
        ]);

         // Create the marketing User
         User::create([
            'name' => 'Staff Marketing',
            'email' => 'staff.marketing@eggsystem.com',
            'role' => 'staff-marketing',
            'password' => Hash::make('password')
        ]);

         // Create the production User
        User::create([
            'name' => 'Staff Production',
            'email' => 'staff.production@eggsystem.com',
            'role' => 'staff-production',
            'password' => Hash::make('password')
        ]);
        
    }
}
