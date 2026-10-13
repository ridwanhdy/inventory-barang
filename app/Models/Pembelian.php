<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Pembelian extends Model
{
    protected $fillable = [
        'pemasok_id',
        'tanggal_pembelian',
        'catatan',
    ];

    protected $casts = [
        'tanggal_pembelian' => 'date',
    ];

    public function pemasok(): BelongsTo
    {
        return $this->belongsTo(Pemasok::class);
    }



    public function pembelianDetails(): HasMany
    {
        return $this->hasMany(PembelianDetail::class);
    }

    public function delete()
    {
        // Deleting details through their models reverses stock; SQL cascades skip events.
        return DB::transaction(function () {
            if (! $this->exists) {
                return parent::delete();
            }

            static::query()->lockForUpdate()->findOrFail($this->getKey());

            foreach ($this->pembelianDetails()->orderBy('id')->lockForUpdate()->get() as $detail) {
                $detail->delete();
            }

            return parent::delete();
        });
    }
}
