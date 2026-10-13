<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PembelianDetail extends Model
{
    protected $fillable = [
        'pembelian_id',
        'bahan_baku_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function save(array $options = [])
    {
        return DB::transaction(fn () => parent::save($options));
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

    protected static function booted()
    {
        static::saving(function (PembelianDetail $detail): void {
            Validator::make(
                ['quantity' => $detail->getAttributes()['quantity'] ?? null],
                ['quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:999999.99']],
                [
                    'quantity.gt' => 'Jumlah diterima harus lebih dari nol.',
                    'quantity.decimal' => 'Jumlah diterima dapat menggunakan maksimal dua angka desimal.',
                ],
            )->validate();
        });

        static::creating(function (PembelianDetail $detail): void {
            static::adjustStock([(int) $detail->bahan_baku_id => (float) $detail->quantity]);
        });

        static::updating(function (PembelianDetail $detail): void {
            if (! $detail->isDirty(['bahan_baku_id', 'quantity'])) {
                return;
            }

            $previous = static::query()->lockForUpdate()->findOrFail($detail->getKey());

            // Preserve the latest quantity if this update only changes the material.
            if (! $detail->isDirty('quantity')) {
                $detail->quantity = $previous->quantity;
            }

            $changes = [(int) $previous->bahan_baku_id => -(float) $previous->quantity];
            $materialId = (int) $detail->bahan_baku_id;
            $changes[$materialId] = ($changes[$materialId] ?? 0) + (float) $detail->quantity;
            static::adjustStock($changes);
        });

        static::deleting(function (PembelianDetail $detail): void {
            $previous = static::query()->lockForUpdate()->findOrFail($detail->getKey());
            static::adjustStock([(int) $previous->bahan_baku_id => -(float) $previous->quantity]);
        });
    }

    protected static function adjustStock(array $changes): void
    {
        // Lock material rows in a stable order, including when its stock row is missing.
        ksort($changes);

        foreach ($changes as $materialId => $change) {
            $material = BahanBaku::query()->lockForUpdate()->find($materialId);

            if (! $material) {
                throw ValidationException::withMessages(['quantity' => 'Bahan baku yang dipilih tidak tersedia.']);
            }

            if (abs($change) < 0.000001) {
                continue;
            }

            $stock = BahanBakuDetail::query()
                ->where('bahan_baku_id', $materialId)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            $nextStock = round((float) ($stock?->stok ?? 0) + $change, 2);

            if ($nextStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Koreksi penerimaan untuk ' . $material->nama_bahan . ' tidak dapat disimpan karena bahan sudah digunakan dan stok akan menjadi negatif.',
                ]);
            }

            if ($nextStock > 999999.99) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah diterima melampaui kapasitas pencatatan stok bahan baku.']);
            }

            if ($stock) {
                $stock->stok = $nextStock;
                $stock->save();
            } else {
                BahanBakuDetail::create(['bahan_baku_id' => $materialId, 'stok' => $nextStock]);
            }
        }
    }

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function bahanBaku(): BelongsTo
    {
        return $this->belongsTo(BahanBaku::class);
    }
}
