<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Product;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use App\Support\ProductionWorkflow;
use Database\Seeders\Support\DemoPeriod;
use Database\Seeders\Support\KaosPolosCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProduksiSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->where('nama_product', 'like', 'Kaos Polos%')
            ->whereIn('ukuran', KaosPolosCatalog::sizes())
            ->whereIn('warna', KaosPolosCatalog::colors())
            ->whereIn('bahan', array_keys(KaosPolosCatalog::fabrics()))
            ->orderBy('id')->get()
            ->filter(fn (Product $product): bool => $product->nama_product === "Kaos Polos {$product->bahan} - {$product->warna} - {$product->ukuran}")
            ->values();
        $expected = count(KaosPolosCatalog::sizes()) * count(KaosPolosCatalog::colors()) * count(KaosPolosCatalog::fabrics());
        if ($products->count() !== $expected) {
            throw new LogicException('Jalankan ProductSeeder untuk menyiapkan seluruh varian kaos polos sebelum produksi.');
        }
        $materials = BahanBaku::query()->get()->keyBy('nama_bahan');

        // Each size/color/fabric combination has its own accepted production output.
        foreach ($products as $index => $product) {
            $day = (int) floor($index * 28 / $products->count());
            $this->seedBatch($product, $materials, $day, 20 + ($index % 11), $index, 'SELESAI');
        }

        // Keep activity on the last two dates, with examples of every lifecycle state.
        $this->seedBatch($products[0], $materials, 28, 24, $expected, 'SELESAI');
        $this->seedBatch($products[1], $materials, 29, 26, $expected + 1, 'SELESAI');
        $this->seedBatch($products[2], $materials, 28, 24, $expected + 2, 'RENCANA');
        $this->seedBatch($products[3], $materials, 29, 26, $expected + 3, 'PROSES');
        $this->seedBatch($products[4], $materials, 28, 22, $expected + 4, 'BATAL-RENCANA');
        $this->seedBatch($products[5], $materials, 29, 25, $expected + 5, 'BATAL-PROSES');

        $this->command?->info('Produksi 30 hari kaos polos, hasil QC, sisa bahan, rencana dan pembatalan berhasil disiapkan.');
    }

    private function seedBatch(Product $product, Collection $materials, int $day, int $target, int $index, string $scenario): void
    {
        $note = sprintf('DEMO-KAOS-PRODUKSI-%04d | %s | %s', $index + 1, $scenario, $product->nama_product);
        if (Produksi::query()->where('catatan', $note)->exists()) {
            return;
        }

        $sizeIndex = array_search($product->ukuran, KaosPolosCatalog::sizes(), true);
        $requirements = [
            KaosPolosCatalog::fabricName($product->bahan, $product->warna) => round($target * (0.65 + $sizeIndex * 0.05) + 0.25, 2),
            KaosPolosCatalog::ribName($product->warna) => round($target * 0.08 + 0.05, 2),
            'Benang Jahit Polyester' => round($target * 0.002, 2),
            'Label Ukuran Kaos' => $target,
            'Label Merek Kaos' => $target,
            'Plastik Kemasan Kaos' => $target,
        ];
        foreach ($requirements as $name => $quantity) {
            if (! $materials->has($name)) {
                throw new LogicException('Bahan baku produksi belum tersedia: '.$name);
            }
        }

        DB::transaction(function () use ($product, $materials, $day, $target, $index, $scenario, $note, $requirements): void {
            $production = DemoPeriod::at(DemoPeriod::day($day, 8), function () use ($product, $materials, $day, $target, $note, $requirements): Produksi {
                $production = Produksi::create([
                    'product_id' => $product->id,
                    'jumlah_produksi' => $target,
                    'produksi_mulai' => DemoPeriod::day($day)->toDateString(),
                    'catatan' => $note,
                ]);
                foreach ($requirements as $name => $quantity) {
                    ProduksiDetail::create([
                        'produksi_id' => $production->id,
                        'bahan_baku_id' => $materials[$name]->id,
                        'jumlah_digunakan' => $quantity,
                    ]);
                }

                return $production;
            });

            if ($scenario === 'RENCANA') {
                return;
            }
            if ($scenario === 'BATAL-RENCANA') {
                DemoPeriod::at(DemoPeriod::day($day, 9), fn () => ProductionWorkflow::cancel(
                    $production, 'Rencana dibatalkan karena perubahan pesanan; belum ada bahan dikeluarkan.',
                ));

                return;
            }

            $production = DemoPeriod::at(DemoPeriod::day($day, 9), fn () => ProductionWorkflow::start(
                $production, DemoPeriod::day($day)->toDateString(),
            ));
            // The workflow records its legacy date-only start at midnight; keep the demo's actual 09.00 WIB start.
            DB::table('produksis')->where('id', $production->id)->where('status', Produksi::PROCESS)->update([
                'produksi_dimulai_at' => DemoPeriod::day($day, 9)->utc(),
            ]);
            $production->refresh();
            if ($index % 11 === 0) {
                DemoPeriod::at(DemoPeriod::day($day, 10), fn () => ProductionWorkflow::issue(
                    $production, (int) $materials['Benang Jahit Polyester']->id, 0.01,
                ));
            }
            if ($scenario === 'PROSES') {
                return;
            }

            $details = $production->produksiDetail()->orderBy('id')->get();
            $fabricId = (int) $materials[KaosPolosCatalog::fabricName($product->bahan, $product->warna)]->id;
            $ribId = (int) $materials[KaosPolosCatalog::ribName($product->warna)]->id;
            $threadId = (int) $materials['Benang Jahit Polyester']->id;
            $returns = [];
            foreach ($details as $detail) {
                $materialId = (int) $detail->bahan_baku_id;
                if ($scenario === 'BATAL-PROSES') {
                    // Only trial-cut fabric and a little thread are consumed; other supplies return.
                    $used = $materialId === $fabricId ? 0.5 : ($materialId === $threadId ? 0.01 : 0);
                    $returns[$detail->id] = round((float) $detail->jumlah_digunakan - $used, 2);
                } elseif ($materialId === $fabricId && $index % 4 === 0) {
                    $returns[$detail->id] = 0.25;
                } elseif ($materialId === $ribId && $index % 6 === 0) {
                    $returns[$detail->id] = 0.05;
                }
            }
            if ($scenario === 'BATAL-PROSES') {
                DemoPeriod::at(DemoPeriod::day($day, 12), fn () => ProductionWorkflow::cancel(
                    $production, 'Uji potong tidak lolos QC. Sisa bahan dan seluruh label/kemasan dikembalikan; 0,50 meter kain serta 0,01 kg benang terpakai untuk sampel.', $returns,
                ));

                return;
            }

            $damaged = max($target - 28, $index % 9 === 0 ? 2 : ($index % 5 === 0 ? 1 : 0));
            $good = $target - $damaged;
            DemoPeriod::at(DemoPeriod::day($day, 12), fn () => ProductionWorkflow::complete(
                $production, $good, $damaged, DemoPeriod::day($day)->toDateString(),
                $damaged > 0
                    ? 'QC: '.$good.' kaos layak jual dan '.$damaged.' kaos cacat jahitan; sisa kain/rib yang masih layak dikembalikan sesuai catatan bahan.'
                    : 'QC: seluruh kaos layak jual; sisa kain/rib yang masih layak dikembalikan sesuai catatan bahan.',
                $returns,
            ));
        });
    }
}
