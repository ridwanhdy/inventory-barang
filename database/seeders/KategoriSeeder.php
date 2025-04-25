<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategoris = [
            ['nama_kategori' => 'Kaos Polos'],
            ['nama_kategori' => 'Kaos Sablon'],
            ['nama_kategori' => 'Jaket Hoodie'],
            ['nama_kategori' => 'Jaket Bomber'],
            ['nama_kategori' => 'Jaket Varsity'],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::create($kategori);
        }
    }
} 