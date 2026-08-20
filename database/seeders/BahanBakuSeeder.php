<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class BahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        $satuans = Satuan::pluck('id', 'nama_satuan');
        $kategoris = Kategori::pluck('id', 'nama_kategori');

        $bahanBaku = [
            // Kain untuk kaos
            [
                'nama_bahan' => 'Kain Cotton Combed 30s',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            [
                'nama_bahan' => 'Kain Cotton Combed 24s',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            // Kain untuk jaket
            [
                'nama_bahan' => 'Kain Fleece',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            [
                'nama_bahan' => 'Kain Taslan',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            [
                'nama_bahan' => 'Kain Wool Blend',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            // Bahan penunjang kaos
            [
                'nama_bahan' => 'Rib Cotton 1x1',
                'satuan' => 'Meter',
                'kategori' => 'Kain',
            ],
            // Bahan penunjang umum
            [
                'nama_bahan' => 'Benang Jahit Polyester',
                'satuan' => 'Kg',
                'kategori' => 'Benang',
            ],
            [
                'nama_bahan' => 'Kancing Plastik 4 Lubang',
                'satuan' => 'Pcs',
                'kategori' => 'Kancing',
            ],
            [
                'nama_bahan' => 'Resleting YKK 50cm',
                'satuan' => 'Pcs',
                'kategori' => 'Resleting',
            ],
            [
                'nama_bahan' => 'Resleting YKK 70cm',
                'satuan' => 'Pcs',
                'kategori' => 'Resleting',
            ],
            [
                'nama_bahan' => 'Label Woven',
                'satuan' => 'Pcs',
                'kategori' => 'Aksesoris',
            ],
            [
                'nama_bahan' => 'Tinta Sablon Plastisol',
                'satuan' => 'Kg',
                'kategori' => 'Aksesoris',
            ],
        ];

        foreach ($bahanBaku as $bahan) {
            BahanBaku::firstOrCreate(
                ['nama_bahan' => $bahan['nama_bahan']],
                [
                    'satuan_id' => $satuans[$bahan['satuan']],
                    'kategori_id' => $kategoris[$bahan['kategori']],
                    'jenis' => 'bahan baku',
                ]
            );
        }

        $this->command->info('Bahan baku kaos & jaket berhasil di-seed!');
    }
}
