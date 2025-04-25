<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'nama_product' => 'Kaos Polos Premium',
                'jenis_id' => 1, // Kaos
                'kategori_id' => 1, // Kaos Polos
                'satuan_id' => 1, // Pcs
                'ukuran' => 'M',
                'warna' => 'Hitam',
                'bahan' => 'Cotton Combed 30s',
                'stok' => 100,
                'harga_jual' => 45000,
                'foto' => 'kaos-polos.jpg',
            ],
            [
                'nama_product' => 'Kaos Sablon Custom',
                'jenis_id' => 1, // Kaos
                'kategori_id' => 2, // Kaos Sablon
                'satuan_id' => 1, // Pcs
                'ukuran' => 'L',
                'warna' => 'Putih',
                'bahan' => 'Cotton Combed 24s',
                'stok' => 50,
                'harga_jual' => 65000,
                'foto' => 'kaos-sablon.jpg',
            ],
            [
                'nama_product' => 'Jaket Hoodie Basic',
                'jenis_id' => 2, // Jaket
                'kategori_id' => 3, // Jaket Hoodie
                'satuan_id' => 1, // Pcs
                'ukuran' => 'XL',
                'warna' => 'Navy',
                'bahan' => 'Fleece',
                'stok' => 75,
                'harga_jual' => 150000,
                'foto' => 'hoodie.jpg',
            ],
            [
                'nama_product' => 'Jaket Bomber Premium',
                'jenis_id' => 2, // Jaket
                'kategori_id' => 4, // Jaket Bomber
                'satuan_id' => 1, // Pcs
                'ukuran' => 'L',
                'warna' => 'Olive',
                'bahan' => 'Taslan',
                'stok' => 30,
                'harga_jual' => 200000,
                'foto' => 'bomber.jpg',
            ],
            [
                'nama_product' => 'Jaket Varsity Classic',
                'jenis_id' => 2, // Jaket
                'kategori_id' => 5, // Jaket Varsity
                'satuan_id' => 1, // Pcs
                'ukuran' => 'M',
                'warna' => 'Merah-Putih',
                'bahan' => 'Wool',
                'stok' => 25,
                'harga_jual' => 250000,
                'foto' => 'varsity.jpg',
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
} 