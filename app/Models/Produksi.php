<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    protected $fillable = [
        'product_id',
        'jumlah_produksi',
        'produksi_mulai',
        'produksi_selesai',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function produksiDetail()
    {
        return $this->hasMany(ProduksiDetail::class);
    }

    protected static function booted()
    {
        static::updated(function ($produksi) {
            if (
                $produksi->status === 'Selesai' &&
                $produksi->getOriginal('status') !== 'Selesai'
            ) {
                // Tambahkan stok produk
                $produksi->product->increment('stok', $produksi->jumlah_produksi);
            }
        });
    }
}
