<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembelianDetail extends Model
{
    protected $fillable = [
        'pembelian_id',
        'bahan_baku_id',
        'quantity',
        'harga',
        'subtotal',
    ];

    protected $casts = [
        'harga' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::created(function ($detail) {
            // Cari BahanBakuDetail terkait
            $bahanBakuDetail = \App\Models\BahanBakuDetail::where('bahan_baku_id', $detail->bahan_baku_id)->first();
            if ($bahanBakuDetail) {
                $bahanBakuDetail->increment('stok', $detail->quantity);
            }
        });
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
