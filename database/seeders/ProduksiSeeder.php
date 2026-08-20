<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Product;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use Illuminate\Database\Seeder;

class ProduksiSeeder extends Seeder
{
    public function run(): void
    {
        $produksis = [
            [
                'product' => 'Kaos Polos Premium Hitam',
                'jumlah_produksi' => 50,
                'produksi_mulai' => now()->subDays(10),
                'produksi_selesai' => now()->subDays(8),
                'status' => 'Selesai',
                'bahan' => [
                    ['nama' => 'Kain Cotton Combed 30s', 'jumlah' => 25.5],
                    ['nama' => 'Rib Cotton 1x1', 'jumlah' => 5.0],
                    ['nama' => 'Benang Jahit Polyester', 'jumlah' => 2.0],
                    ['nama' => 'Label Woven', 'jumlah' => 50],
                ],
            ],
            [
                'product' => 'Kaos Sablon Custom Navy',
                'jumlah_produksi' => 30,
                'produksi_mulai' => now()->subDays(7),
                'produksi_selesai' => now()->subDays(5),
                'status' => 'Selesai',
                'bahan' => [
                    ['nama' => 'Kain Cotton Combed 24s', 'jumlah' => 18.0],
                    ['nama' => 'Rib Cotton 1x1', 'jumlah' => 3.0],
                    ['nama' => 'Benang Jahit Polyester', 'jumlah' => 1.5],
                    ['nama' => 'Tinta Sablon Plastisol', 'jumlah' => 1.0],
                    ['nama' => 'Label Woven', 'jumlah' => 30],
                ],
            ],
            [
                'product' => 'Jaket Hoodie Basic Navy',
                'jumlah_produksi' => 20,
                'produksi_mulai' => now()->subDays(3),
                'produksi_selesai' => null,
                'status' => 'Proses',
                'bahan' => [
                    ['nama' => 'Kain Fleece', 'jumlah' => 40.0],
                    ['nama' => 'Resleting YKK 50cm', 'jumlah' => 20],
                    ['nama' => 'Benang Jahit Polyester', 'jumlah' => 3.0],
                    ['nama' => 'Label Woven', 'jumlah' => 20],
                ],
            ],
            [
                'product' => 'Jaket Bomber Premium Olive',
                'jumlah_produksi' => 15,
                'produksi_mulai' => now()->subDays(1),
                'produksi_selesai' => null,
                'status' => 'Proses',
                'bahan' => [
                    ['nama' => 'Kain Taslan', 'jumlah' => 30.0],
                    ['nama' => 'Resleting YKK 70cm', 'jumlah' => 15],
                    ['nama' => 'Kancing Plastik 4 Lubang', 'jumlah' => 60],
                    ['nama' => 'Benang Jahit Polyester', 'jumlah' => 2.5],
                    ['nama' => 'Label Woven', 'jumlah' => 15],
                ],
            ],
        ];

        foreach ($produksis as $data) {
            $product = Product::where('nama_product', $data['product'])->first();
            if (!$product) {
                continue;
            }

            $produksi = Produksi::create([
                'product_id' => $product->id,
                'jumlah_produksi' => $data['jumlah_produksi'],
                'produksi_mulai' => $data['produksi_mulai'],
                'produksi_selesai' => $data['produksi_selesai'],
                'status' => $data['status'],
            ]);

            foreach ($data['bahan'] as $bahan) {
                $bahanBaku = BahanBaku::where('nama_bahan', $bahan['nama'])->first();
                if ($bahanBaku) {
                    ProduksiDetail::create([
                        'produksi_id' => $produksi->id,
                        'bahan_baku_id' => $bahanBaku->id,
                        'jumlah_digunakan' => $bahan['jumlah'],
                    ]);
                }
            }
        }

        $this->command->info('Data produksi kaos & jaket berhasil di-seed!');
    }
}
