<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pembelian extends Model
{
    protected $fillable = [
        'pemasok_id',
        'tanggal_pembelian',
        'total_harga',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
        'total_harga' => 'decimal:2',
    ];

    public function pemasok(): BelongsTo
    {
        return $this->belongsTo(Pemasok::class);
    }



    public function pembelianDetails(): HasMany
    {
        return $this->hasMany(PembelianDetail::class);
    }
}
