<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductDetail;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class ProductDetailSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            foreach (Product::whereHas('kategori', fn ($query) => $query->where('nama_kategori', 'Kaos Polos'))->cursor() as $product) {
                ProductDetail::firstOrCreate(['product_id' => $product->id], ['stok' => 0]);
            }
        });
    }
}
