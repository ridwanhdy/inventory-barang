<?php

namespace App\Support;

use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProductionWorkflow
{
    public static function issue(Produksi $record, int $materialId, float $quantity): ProduksiDetail
    {
        return DB::transaction(function () use ($record, $materialId, $quantity) {
            $production = static::lockedProduction($record);
            if (! $production->isProcessing()) {
                static::invalidStatus('Bahan tambahan hanya dapat dikeluarkan untuk produksi yang sedang berjalan.');
            }
            Validator::make(['produksiDetail' => $quantity], [
                'produksiDetail' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
            ], [
                'produksiDetail.gt' => 'Jumlah bahan tambahan harus lebih dari nol.',
                'produksiDetail.decimal' => 'Jumlah bahan tambahan dapat menggunakan maksimal dua angka desimal.',
            ])->validate();
            $detail = $production->produksiDetail()->where('bahan_baku_id', $materialId)->orderBy('id')->lockForUpdate()->first();
            if (! $detail) {
                return $production->produksiDetail()->create(['bahan_baku_id' => $materialId, 'jumlah_digunakan' => $quantity]);
            }
            $totalIssued = round((float) $detail->jumlah_digunakan + $quantity, 2);
            if ($totalIssued > 9999999999.99) {
                throw ValidationException::withMessages(['produksiDetail' => 'Total bahan yang dikeluarkan melampaui kapasitas pencatatan produksi.']);
            }
            static::changeMaterialStock([$materialId => -$quantity]);
            DB::table('produksi_details')->where('id', $detail->id)->update(['jumlah_digunakan' => $totalIssued, 'updated_at' => now()]);

            return $detail->refresh();
        }, 3);
    }

    public static function start(Produksi $record, string $date): Produksi
    {
        return DB::transaction(function () use ($record, $date) {
            $production = static::lockedProduction($record);
            if (! $production->isDraft()) {
                static::invalidStatus('Hanya Rencana yang dapat dimulai. Produksi ini sudah dimulai atau ditutup.');
            }
            static::validateDate($date, 'produksi_mulai');
            $details = $production->produksiDetail()->orderBy('bahan_baku_id')->orderBy('id')->lockForUpdate()->get();
            if ($details->isEmpty()) {
                throw ValidationException::withMessages(['produksiDetail' => 'Tambahkan minimal satu bahan baku sebelum memulai produksi.']);
            }
            $changes = [];
            foreach ($details as $detail) {
                Validator::make(['produksiDetail' => $detail->getRawOriginal('jumlah_digunakan')], [
                    'produksiDetail' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
                ], ['produksiDetail.gt' => 'Jumlah setiap bahan harus lebih dari nol sebelum produksi dimulai.'])->validate();
                $id = (int) $detail->bahan_baku_id;
                $changes[$id] = ($changes[$id] ?? 0) - (float) $detail->jumlah_digunakan;
            }
            static::changeMaterialStock($changes);
            static::writeState($production, ['status' => Produksi::PROCESS, 'produksi_mulai' => $date, 'produksi_dimulai_at' => $date.' 00:00:00']);

            return $production->refresh();
        }, 3);
    }

    public static function complete(Produksi $record, int $good, int $damaged, string $date, ?string $notes = null, array $returns = []): Produksi
    {
        return DB::transaction(function () use ($record, $good, $damaged, $date, $notes, $returns) {
            $production = static::lockedProduction($record);
            if (! $production->isProcessing()) {
                static::invalidStatus('Hanya produksi yang sedang berjalan dapat diselesaikan. Produksi ini belum dimulai atau sudah ditutup.');
            }
            Validator::make(['jumlah_hasil' => $good, 'jumlah_rusak' => $damaged], [
                'jumlah_hasil' => ['required', 'integer', 'min:0', 'max:2147483647'],
                'jumlah_rusak' => ['required', 'integer', 'min:0', 'max:2147483647'],
            ], ['*.min' => 'Jumlah hasil tidak boleh negatif.'])->validate();
            if ($good + $damaged <= 0) {
                throw ValidationException::withMessages(['jumlah_hasil' => 'Catat minimal satu unit hasil baik atau rusak. Jika produksi tidak menghasilkan produk, gunakan Batalkan produksi.']);
            }
            $notes = trim($notes ?? '');
            if ($good + $damaged !== $production->jumlah_produksi && $notes === '') {
                throw ValidationException::withMessages(['catatan' => 'Jelaskan selisih hasil aktual dengan target produksi pada catatan.']);
            }
            static::validateDate($date, 'produksi_selesai', $production->produksi_mulai->toDateString());
            static::returnMaterials($production, $returns);
            if ($good > 0) {
                // The master lock also protects creation of a missing authoritative stock row.
                $product = Product::query()->whereKey($production->product_id)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['jumlah_hasil' => 'Produk hasil produksi tidak tersedia.']);
                }
                $stocks = ProductDetail::query()->where('product_id', $product->id)->orderBy('id')->lockForUpdate()->get();
                if ($stocks->contains(fn ($stock) => (float) $stock->stok < 0) || (float) $stocks->sum('stok') + $good > 999999.99) {
                    throw ValidationException::withMessages(['jumlah_hasil' => 'Hasil baik melampaui kapasitas pencatatan stok produk atau stok produk perlu diperbaiki.']);
                }
                // Credit accepted output exactly once; ProductDetail and the master mirror agree.
                SalesStock::change([(int) $production->product_id => $good]);
            }
            static::writeState($production, [
                'status' => Produksi::COMPLETED, 'jumlah_hasil' => $good, 'jumlah_rusak' => $damaged,
                'produksi_selesai' => $date, 'catatan_hasil' => $notes === '' ? null : $notes,
            ]);

            return $production->refresh();
        }, 3);
    }

    public static function cancel(Produksi $record, string $reason, array $returns = []): Produksi
    {
        return DB::transaction(function () use ($record, $reason, $returns) {
            $production = static::lockedProduction($record);
            if ($production->isClosed()) {
                static::invalidStatus('Produksi sudah ditutup dan tidak dapat dibatalkan lagi.');
            }
            $reason = trim($reason);
            if ($reason === '') {
                throw ValidationException::withMessages(['alasan_batal' => 'Tuliskan alasan pembatalan produksi.']);
            }
            if ($production->isDraft()) {
                if ($returns !== []) {
                    throw ValidationException::withMessages(['produksiDetail' => 'Rencana belum mengeluarkan bahan, sehingga tidak ada bahan yang perlu dikembalikan.']);
                }
            } elseif ($production->isProcessing()) {
                static::returnMaterials($production, $returns);
            } else {
                static::invalidStatus('Tahap produksi tidak dikenali. Muat ulang halaman.');
            }
            static::writeState($production, [
                'status' => Produksi::CANCELLED, 'alasan_batal' => $reason, 'tanggal_batal' => today('Asia/Jakarta')->toDateString(),
            ]);

            return $production->refresh();
        }, 3);
    }

    /** @internal Shared by production start, explicit returns, and additional material issues. */
    public static function changeMaterialStock(array $changes): void
    {
        ksort($changes);
        DB::transaction(function () use ($changes) {
            foreach ($changes as $id => $change) {
                $material = BahanBaku::query()->whereKey($id)->lockForUpdate()->first();
                if (! $material) {
                    throw ValidationException::withMessages(['produksiDetail' => 'Bahan baku yang dipilih tidak tersedia.']);
                }
                $stocks = BahanBakuDetail::query()->where('bahan_baku_id', $id)->orderBy('id')->lockForUpdate()->get();
                $changeCents = (int) round((float) $change * 100);
                if ($changeCents === 0) {
                    continue;
                }
                $availableCents = $stocks->sum(fn ($stock) => (int) round((float) $stock->stok * 100));
                if ($stocks->contains(fn ($stock) => (float) $stock->stok < 0) || ($changeCents < 0 && $availableCents < -$changeCents)) {
                    throw ValidationException::withMessages(['produksiDetail' => 'Stok '.$material->nama_bahan.' tidak mencukupi atau perlu diperbaiki. Tersedia '.number_format(max(0, $availableCents) / 100, 2, ',', '.').'. Kurangi jumlah bahan atau catat penerimaan bahan terlebih dahulu.']);
                }
                if ($changeCents > 0) {
                    if ($availableCents + $changeCents > 99999999) {
                        throw ValidationException::withMessages(['produksiDetail' => 'Pengembalian '.$material->nama_bahan.' melampaui kapasitas pencatatan stok bahan baku.']);
                    }
                    $stock = $stocks->first();
                    if ($stock) {
                        $stock->update(['stok' => round((float) $stock->stok + $changeCents / 100, 2)]);
                    } else {
                        BahanBakuDetail::create(['bahan_baku_id' => $id, 'stok' => $changeCents / 100]);
                    }
                } else {
                    $neededCents = -$changeCents;
                    foreach ($stocks as $stock) {
                        $stockCents = (int) round((float) $stock->stok * 100);
                        $takeCents = min($stockCents, $neededCents);
                        if ($takeCents > 0) {
                            $stock->update(['stok' => ($stockCents - $takeCents) / 100]);
                            $neededCents -= $takeCents;
                        }
                        if ($neededCents === 0) {
                            break;
                        }
                    }
                }
            }
        }, 3);
    }

    private static function lockedProduction(Produksi $record): Produksi
    {
        $production = Produksi::query()->whereKey($record->getKey())->lockForUpdate()->first();
        if (! $production) {
            static::invalidStatus('Produksi tidak tersedia. Muat ulang halaman.');
        }

        return $production;
    }

    private static function validateDate(string $date, string $field, ?string $earliest = null): void
    {
        $rules = ['required', 'date_format:Y-m-d', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()];
        if ($earliest !== null) {
            $rules[] = 'after_or_equal:'.$earliest;
        }
        Validator::make([$field => $date], [$field => $rules], [
            $field.'.date_format' => 'Tanggal produksi tidak valid.',
            $field.'.before_or_equal' => 'Tanggal aktual produksi tidak boleh melewati hari ini.',
            $field.'.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai produksi.',
        ])->validate();
    }

    private static function returnMaterials(Produksi $production, array $returns): void
    {
        $details = $production->produksiDetail()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $changes = [];
        foreach ($returns as $id => $quantity) {
            $detail = $details->get($id);
            if (! $detail) {
                throw ValidationException::withMessages(['produksiDetail' => 'Bahan yang dikembalikan tidak tercatat pada produksi ini. Muat ulang halaman.']);
            }
            Validator::make(['produksiDetail' => $quantity], [
                'produksiDetail' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:'.$detail->jumlah_digunakan],
            ], [
                'produksiDetail.min' => 'Jumlah bahan dikembalikan tidak boleh negatif.',
                'produksiDetail.max' => 'Jumlah bahan dikembalikan tidak boleh melebihi jumlah yang dikeluarkan.',
                'produksiDetail.decimal' => 'Jumlah bahan dikembalikan dapat menggunakan maksimal dua angka desimal.',
            ])->validate();
            $materialId = (int) $detail->bahan_baku_id;
            $changes[$materialId] = ($changes[$materialId] ?? 0) + (float) $quantity;
            // The terminal workflow writes the audit value; ordinary detail edits are locked.
            DB::table('produksi_details')->where('id', $detail->id)->update(['jumlah_dikembalikan' => $quantity, 'updated_at' => now()]);
        }
        static::changeMaterialStock($changes);
    }

    private static function writeState(Produksi $production, array $attributes): void
    {
        // Ordinary model save prohibits lifecycle edits; only this locked workflow writes them.
        Produksi::query()->whereKey($production->getKey())->update($attributes + ['updated_at' => now()]);
    }

    private static function invalidStatus(string $message): never
    {
        throw ValidationException::withMessages(['status' => $message]);
    }
}
