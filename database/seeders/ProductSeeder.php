<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Product;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $pcs = Satuan::where('nama_satuan', 'Pcs')->first();

        $products = [
            // Kaos
            [
                'nama_product' => 'Kaos Polos Premium Hitam',
                'kategori' => 'Kaos Polos',
                'ukuran' => 'M',
                'warna' => 'Hitam',
                'bahan' => 'Cotton Combed 30s',
                'harga_jual' => 45000,
                'foto' => 'kaos-polos-hitam.jpg',
            ],
            [
                'nama_product' => 'Kaos Polos Premium Putih',
                'kategori' => 'Kaos Polos',
                'ukuran' => 'L',
                'warna' => 'Putih',
                'bahan' => 'Cotton Combed 30s',
                'harga_jual' => 45000,
                'foto' => 'kaos-polos-putih.jpg',
            ],
            [
                'nama_product' => 'Kaos Sablon Custom Navy',
                'kategori' => 'Kaos Sablon',
                'ukuran' => 'L',
                'warna' => 'Navy',
                'bahan' => 'Cotton Combed 24s',
                'harga_jual' => 65000,
                'foto' => 'kaos-sablon-navy.jpg',
            ],
            [
                'nama_product' => 'Kaos Sablon Custom Merah',
                'kategori' => 'Kaos Sablon',
                'ukuran' => 'M',
                'warna' => 'Merah',
                'bahan' => 'Cotton Combed 24s',
                'harga_jual' => 65000,
                'foto' => 'kaos-sablon-merah.jpg',
            ],
            // Jaket
            [
                'nama_product' => 'Jaket Hoodie Basic Navy',
                'kategori' => 'Jaket Hoodie',
                'ukuran' => 'XL',
                'warna' => 'Navy',
                'bahan' => 'Fleece',
                'harga_jual' => 150000,
                'foto' => 'jaket-hoodie-navy.jpg',
            ],
            [
                'nama_product' => 'Jaket Bomber Premium Olive',
                'kategori' => 'Jaket Bomber',
                'ukuran' => 'L',
                'warna' => 'Olive',
                'bahan' => 'Taslan',
                'harga_jual' => 200000,
                'foto' => 'jaket-bomber-olive.jpg',
            ],
            [
                'nama_product' => 'Jaket Varsity Classic Merah-Putih',
                'kategori' => 'Jaket Varsity',
                'ukuran' => 'M',
                'warna' => 'Merah-Putih',
                'bahan' => 'Wool Blend',
                'harga_jual' => 250000,
                'foto' => 'jaket-varsity-merah-putih.jpg',
            ],
        ];

        foreach ($products as $product) {
            $kategori = Kategori::where('nama_kategori', $product['kategori'])->first();

            Product::firstOrCreate(
                ['nama_product' => $product['nama_product']],
                [
                    'kategori_id' => $kategori->id,
                    'satuan_id' => $pcs->id,
                    'ukuran' => $product['ukuran'],
                    'warna' => $product['warna'],
                    'bahan' => $product['bahan'],
                    'harga_jual' => $product['harga_jual'],
                    'foto' => $product['foto'],
                ]
            );
        }

        $this->command->info('Produk kaos & jaket berhasil di-seed!');
    }
}
