<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            // Kategori produk jadi (kaos & jaket)
            'Kaos Polos',
            'Kaos Sablon',
            'Jaket Hoodie',
            'Jaket Bomber',
            'Jaket Varsity',
            // Kategori bahan baku
            'Kain',
            'Benang',
            'Kancing',
            'Resleting',
            'Aksesoris',
        ];

        foreach ($kategori as $nama) {
            Kategori::firstOrCreate(['nama_kategori' => $nama]);
        }
    }
}
