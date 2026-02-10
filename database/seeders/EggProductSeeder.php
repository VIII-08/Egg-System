<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EggProduct;


class EggProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['name' => 'Pullets', 'price' => 5.00],
            ['name' => 'Small', 'price' => 6.00],
            ['name' => 'Medium', 'price' => 7.00],
            ['name' => 'Large', 'price' => 8.00],
            ['name' => 'XL', 'price' => 9.00],
            ['name' => 'Jumbo', 'price' => 10.00],
            ['name' => 'Damaged Eggs', 'price' => 0.00],
        ];

        foreach ($products as $product) {
            EggProduct::create($product);
        }
    }
}
