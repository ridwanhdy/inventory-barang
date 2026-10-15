<?php

namespace Database\Seeders;

use App\Models\BahanBakuHistory;
use App\Models\Pembelian;
use App\Models\Produksi;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class BahanBakuHistorySeeder extends Seeder
{
    public function run(): void
    {
        $receipts = Pembelian::query()
            ->where('catatan', 'like', 'DEMO-KAOS-%')
            ->with('pembelianDetails')
            ->orderBy('id')
            ->get();

        foreach ($receipts as $receipt) {
            foreach ($receipt->pembelianDetails as $detail) {
                $this->record(
                    (int) $detail->bahan_baku_id,
                    (float) $detail->quantity,
                    'tambah',
                    "DEMO-KAOS-PENERIMAAN-{$detail->id}: Penerimaan bahan kaos polos.",
                    $detail->created_at,
                );
            }
        }

        $productions = Produksi::query()
            ->where('catatan', 'like', 'DEMO-KAOS-%')
            ->whereNotNull('produksi_dimulai_at')
            ->with('produksiDetail')
            ->orderBy('id')
            ->get();

        foreach ($productions as $production) {
            foreach ($production->produksiDetail as $detail) {
                $this->record(
                    (int) $detail->bahan_baku_id,
                    (float) $detail->jumlah_digunakan,
                    'kurang',
                    "DEMO-KAOS-PEMAKAIAN-{$detail->id}: Pengeluaran bahan untuk produksi kaos polos.",
                    $production->produksi_dimulai_at,
                );

                if ($production->isClosed() && (float) $detail->jumlah_dikembalikan > 0) {
                    $this->record(
                        (int) $detail->bahan_baku_id,
                        (float) $detail->jumlah_dikembalikan,
                        'tambah',
                        "DEMO-KAOS-PENGEMBALIAN-{$detail->id}: Pengembalian sisa bahan produksi kaos polos.",
                        $production->updated_at,
                    );
                }
            }
        }
    }

    private function record(int $materialId, float $quantity, string $type, string $description, CarbonInterface $sourceDate): void
    {
        if ($quantity <= 0) {
            return;
        }

        // Preserve the instant from Eloquent's UTC source date when restoring the historical clock.
        $date = CarbonImmutable::instance($sourceDate)->utc();

        DemoPeriod::at($date, function () use ($materialId, $quantity, $type, $description, $date): void {
            BahanBakuHistory::firstOrCreate(
                ['bahan_baku_id' => $materialId, 'keterangan' => $description],
                ['jumlah_perubahan' => $quantity, 'tipe_perubahan' => $type],
            )->forceFill(['created_at' => $date, 'updated_at' => $date])->save();
        });
    }
}
