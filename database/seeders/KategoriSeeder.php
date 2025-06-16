<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            // Kategori untuk Bahan Jadi
            ['nama_kategori' => 'Kaos Polos'],
            ['nama_kategori' => 'Kaos Sablon'],
            ['nama_kategori' => 'Jaket'],
            ['nama_kategori' => 'Kemeja'],
            ['nama_kategori' => 'Celana'],
            // Kategori untuk Bahan Baku
            ['nama_kategori' => 'Kain'],
            ['nama_kategori' => 'Benang'],
            ['nama_kategori' => 'Kancing'],
            ['nama_kategori' => 'Resleting'],
            ['nama_kategori' => 'Aksesoris'],
        ];

        foreach ($kategori as $item) {
            Kategori::create($item);
        }
    }
} 