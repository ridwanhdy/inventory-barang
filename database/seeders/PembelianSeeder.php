<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Pemasok;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use Database\Seeders\Support\DemoPeriod;
use Database\Seeders\Support\KaosPolosCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class PembelianSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = Pemasok::query()->orderBy('nama_pemasok')->get();
        if ($suppliers->isEmpty()) {
            throw new LogicException('Jalankan PemasokSeeder sebelum PembelianSeeder.');
        }

        $materials = BahanBaku::query()->get()->keyBy('nama_bahan');
        $materialNames = [];
        foreach (array_keys(KaosPolosCatalog::fabrics()) as $fabric) {
            foreach (KaosPolosCatalog::colors() as $color) {
                $materialNames[] = KaosPolosCatalog::fabricName($fabric, $color);
            }
        }
        foreach (KaosPolosCatalog::colors() as $color) {
            $materialNames[] = KaosPolosCatalog::ribName($color);
        }
        $materialNames = array_merge($materialNames, [
            'Benang Jahit Polyester', 'Label Ukuran Kaos', 'Label Merek Kaos', 'Plastik Kemasan Kaos',
        ]);
        foreach ($materialNames as $name) {
            if (! $materials->has($name)) {
                throw new LogicException('Bahan baku katalog belum tersedia: '.$name);
            }
        }

        // Every unit of opening inventory is an actual receipt, so stock reports reconcile.
        $rotatingMaterials = array_values(array_filter($materialNames, fn (string $name): bool => $name !== 'Benang Jahit Polyester'));
        for ($day = 0; $day < 30; $day++) {
            $note = sprintf('DEMO-KAOS-PEMBELIAN-%02d | %s', $day + 1, $day === 0
                ? 'Penerimaan awal seluruh kain, warna, rib dan perlengkapan kaos polos.'
                : 'Pengisian ulang bahan kaos polos untuk produksi harian.');
            if (Pembelian::query()->where('catatan', $note)->exists()) {
                continue;
            }

            $receipts = [];
            if ($day === 0) {
                foreach ($materialNames as $name) {
                    $receipts[$name] = $this->receiptQuantity($name, true);
                }
            } else {
                // Six rotating receipts cover the complete catalog again during the month.
                for ($slot = 0; $slot < 6; $slot++) {
                    $name = $rotatingMaterials[(($day - 1) * 6 + $slot) % count($rotatingMaterials)];
                    $receipts[$name] = $this->receiptQuantity($name, false);
                }
                $receipts['Benang Jahit Polyester'] = 2;
            }

            DemoPeriod::at(DemoPeriod::day($day, 7), function () use ($day, $note, $receipts, $materials, $suppliers): void {
                DB::transaction(function () use ($day, $note, $receipts, $materials, $suppliers): void {
                    $purchase = Pembelian::create([
                        'pemasok_id' => $suppliers[$day % $suppliers->count()]->id,
                        'tanggal_pembelian' => DemoPeriod::day($day)->toDateString(),
                        'catatan' => $note,
                    ]);
                    foreach ($receipts as $name => $quantity) {
                        // Model events credit the authoritative material stock.
                        PembelianDetail::create([
                            'pembelian_id' => $purchase->id,
                            'bahan_baku_id' => $materials[$name]->id,
                            'quantity' => $quantity,
                        ]);
                    }
                });
            });
        }

        $this->command?->info('Penerimaan bahan kaos polos selama 30 hari berhasil disiapkan.');
    }

    private function receiptQuantity(string $name, bool $opening): float
    {
        if ($name === 'Benang Jahit Polyester') {
            return $opening ? 50 : 2;
        }
        if (in_array($name, ['Label Ukuran Kaos', 'Label Merek Kaos', 'Plastik Kemasan Kaos'], true)) {
            return $opening ? 100000 : 500;
        }

        return $opening ? 1000 : 50;
    }
}
