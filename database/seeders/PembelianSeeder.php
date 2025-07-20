<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pembelian;
use App\Models\PembelianDetail;

class PembelianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample purchase records
        $pembelians = [
            [
                'pemasok_id' => 1,
                'tanggal_pembelian' => now()->subDays(10),
                'catatan' => 'Pembelian bahan baku untuk stok awal',
            ],
            [
                'pemasok_id' => 2,
                'tanggal_pembelian' => now()->subDays(7),
                'catatan' => 'Pembelian untuk produksi batch berikutnya',
            ],
            [
                'pemasok_id' => 3,
                'tanggal_pembelian' => now()->subDays(3),
                'catatan' => 'Pembelian bahan baku premium',
            ],
        ];

        foreach ($pembelians as $index => $pembelian) {
            $pembelianRecord = Pembelian::create($pembelian);
            
            // Create purchase details for each purchase
            $bahanBakuIds = [1, 2, 3, 4, 5]; // Sample bahan baku IDs
            $bahanBakuId = $bahanBakuIds[$index % count($bahanBakuIds)];
            
            $quantity = rand(10, 100);
            
            PembelianDetail::create([
                'pembelian_id' => $pembelianRecord->id,
                'bahan_baku_id' => $bahanBakuId,
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
        }

        $this->command->info('Pembelian data seeded successfully!');
    }
} 