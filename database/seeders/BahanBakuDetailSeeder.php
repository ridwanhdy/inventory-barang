<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use Illuminate\Database\Seeder;

class BahanBakuDetailSeeder extends Seeder
{
    public function run(): void
    {
        $stokMap = [
            'Kain Cotton Combed 30s' => 250.50,
            'Kain Cotton Combed 24s' => 180.00,
            'Kain Fleece' => 120.75,
            'Kain Taslan' => 95.00,
            'Kain Wool Blend' => 80.25,
            'Rib Cotton 1x1' => 150.00,
            'Benang Jahit Polyester' => 35.50,
            'Kancing Plastik 4 Lubang' => 500.00,
            'Resleting YKK 50cm' => 200.00,
            'Resleting YKK 70cm' => 150.00,
            'Label Woven' => 1000.00,
            'Tinta Sablon Plastisol' => 25.00,
        ];

        foreach ($stokMap as $namaBahan => $stok) {
            $bahanBaku = BahanBaku::where('nama_bahan', $namaBahan)->first();

            if ($bahanBaku) {
                BahanBakuDetail::where('bahan_baku_id', $bahanBaku->id)->update(['stok' => $stok]);
            }
        }

        $this->command->info('Stok bahan baku kaos & jaket berhasil di-seed!');
    }
}
