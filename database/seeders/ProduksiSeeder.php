<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Produksi;
use App\Models\ProduksiDetail;

class ProduksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample production records
        $produksis = [
            [
                'product_id' => 1,
                'jumlah_produksi' => 50,
                'produksi_mulai' => now()->subDays(5),
                'produksi_selesai' => now()->subDays(3),
                'status' => 'Selesai',
            ],
            [
                'product_id' => 2,
                'jumlah_produksi' => 30,
                'produksi_mulai' => now()->subDays(3),
                'produksi_selesai' => now()->subDays(1),
                'status' => 'Selesai',
            ],
            [
                'product_id' => 3,
                'jumlah_produksi' => 40,
                'produksi_mulai' => now()->subDays(1),
                'produksi_selesai' => null,
                'status' => 'Proses',
            ],
        ];

        foreach ($produksis as $index => $produksi) {
            $produksiRecord = Produksi::create($produksi);
            
            // Create production details for each production
            $bahanBakuIds = [1, 2, 3, 4, 5]; // Sample bahan baku IDs
            $bahanBakuId = $bahanBakuIds[$index % count($bahanBakuIds)];
            
            ProduksiDetail::create([
                'produksi_id' => $produksiRecord->id,
                'bahan_baku_id' => $bahanBakuId,
                'jumlah_digunakan' => rand(5, 20),
            ]);
        }

        $this->command->info('Produksi data seeded successfully!');
    }
} 