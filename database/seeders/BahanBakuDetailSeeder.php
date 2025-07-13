<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BahanBakuDetail;

class BahanBakuDetailSeeder extends Seeder
{
    public function run(): void
    {
        BahanBakuDetail::create([
            'bahan_baku_id' => 1,
            'stok' => 100,
        ]);
        BahanBakuDetail::create([
            'bahan_baku_id' => 2,
            'stok' => 50,
        ]);
        BahanBakuDetail::create([
            'bahan_baku_id' => 3,
            'stok' => 200,
        ]);
    }
} 