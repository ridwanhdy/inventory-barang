<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Pemasok;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use Illuminate\Database\Seeder;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        $pembelians = [
            [
                'pemasok' => 'PT Sukses Jaya Textile',
                'tanggal_pembelian' => now()->subDays(15),
                'catatan' => 'Pembelian kain untuk produksi kaos',
                'details' => [
                    ['nama' => 'Kain Cotton Combed 30s', 'quantity' => 100],
                    ['nama' => 'Kain Cotton Combed 24s', 'quantity' => 80],
                    ['nama' => 'Rib Cotton 1x1', 'quantity' => 50],
                ],
            ],
            [
                'pemasok' => 'CV Maju Bersama',
                'tanggal_pembelian' => now()->subDays(10),
                'catatan' => 'Pembelian kain untuk produksi jaket',
                'details' => [
                    ['nama' => 'Kain Fleece', 'quantity' => 60],
                    ['nama' => 'Kain Taslan', 'quantity' => 50],
                    ['nama' => 'Kain Wool Blend', 'quantity' => 40],
                ],
            ],
            [
                'pemasok' => 'UD Makmur Sejahtera',
                'tanggal_pembelian' => now()->subDays(5),
                'catatan' => 'Pembelian aksesoris jahit & sablon',
                'details' => [
                    ['nama' => 'Benang Jahit Polyester', 'quantity' => 20],
                    ['nama' => 'Kancing Plastik 4 Lubang', 'quantity' => 300],
                    ['nama' => 'Resleting YKK 50cm', 'quantity' => 100],
                    ['nama' => 'Resleting YKK 70cm', 'quantity' => 80],
                    ['nama' => 'Label Woven', 'quantity' => 500],
                    ['nama' => 'Tinta Sablon Plastisol', 'quantity' => 10],
                ],
            ],
        ];

        foreach ($pembelians as $data) {
            $pemasok = Pemasok::where('nama_pemasok', $data['pemasok'])->first();
            if (!$pemasok) {
                continue;
            }

            $pembelian = Pembelian::create([
                'pemasok_id' => $pemasok->id,
                'tanggal_pembelian' => $data['tanggal_pembelian'],
                'catatan' => $data['catatan'],
            ]);

            foreach ($data['details'] as $detail) {
                $bahanBaku = BahanBaku::where('nama_bahan', $detail['nama'])->first();
                if ($bahanBaku) {
                    PembelianDetail::create([
                        'pembelian_id' => $pembelian->id,
                        'bahan_baku_id' => $bahanBaku->id,
                        'quantity' => $detail['quantity'],
                    ]);
                }
            }
        }

        $this->command->info('Data pembelian bahan baku kaos & jaket berhasil di-seed!');
    }
}
