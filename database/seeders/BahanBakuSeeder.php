<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BahanBaku;

class BahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        BahanBaku::create([
            'nama_bahan' => 'Kain Katun',
            'satuan_id' => 1,
            'kategori_id' => 1,
            'jenis' => 'bahan baku',
        ]);
        BahanBaku::create([
            'nama_bahan' => 'Benang',
            'satuan_id' => 2,
            'kategori_id' => 2,
            'jenis' => 'bahan baku',
        ]);
        BahanBaku::create([
            'nama_bahan' => 'Kaos Polos',
            'satuan_id' => 1,
            'kategori_id' => 3,
            'jenis' => 'bahan jadi',
        ]);
    }
} 