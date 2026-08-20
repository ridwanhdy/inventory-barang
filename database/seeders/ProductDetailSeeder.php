<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Database\Seeder;

class ProductDetailSeeder extends Seeder
{
    public function run(): void
    {
        $stokMap = [
            'Kaos Polos Premium Hitam' => 75.00,
            'Kaos Polos Premium Putih' => 60.00,
            'Kaos Sablon Custom Navy' => 45.00,
            'Kaos Sablon Custom Merah' => 40.00,
            'Jaket Hoodie Basic Navy' => 30.00,
            'Jaket Bomber Premium Olive' => 25.00,
            'Jaket Varsity Classic Merah-Putih' => 20.00,
        ];

        foreach ($stokMap as $namaProduct => $stok) {
            $product = Product::where('nama_product', $namaProduct)->first();

            if ($product) {
                ProductDetail::where('product_id', $product->id)->update(['stok' => $stok]);
            }
        }

        $this->command->info('Stok produk kaos & jaket berhasil di-seed!');
    }
}
