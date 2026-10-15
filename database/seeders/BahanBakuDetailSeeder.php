<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class BahanBakuDetailSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            foreach (BahanBaku::cursor() as $material) {
                BahanBakuDetail::firstOrCreate(['bahan_baku_id' => $material->id], ['stok' => 0]);
            }
        });
    }
}
