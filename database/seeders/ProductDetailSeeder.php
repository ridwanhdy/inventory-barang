<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductDetail;

class ProductDetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all products
        $products = Product::all();
        
        if ($products->count() > 0) {
            foreach ($products as $product) {
                // Create product detail with random stock
                ProductDetail::create([
                    'product_id' => $product->id,
                    'stok' => rand(10, 100), // Random stock between 10-100
                ]);
            }
            
            $this->command->info('ProductDetail data seeded successfully!');
        } else {
            $this->command->warn('No products found. Please seed products first.');
        }
    }
}
