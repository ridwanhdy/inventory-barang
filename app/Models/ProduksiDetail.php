<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class ProduksiDetail extends Model
{
    protected $fillable = [
        'produksi_id',
        'bahan_baku_id',
        'jumlah_digunakan',
    ];

    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'bahan_baku_id');
    }

    protected static function booted()
    {
        static::creating(function ($detail) {
            // Ambil stok dari BahanBakuDetail dan langsung kurangi tanpa validasi
            $bahanBakuDetail = \App\Models\BahanBakuDetail::where('bahan_baku_id', $detail->bahan_baku_id)->first();
            if ($bahanBakuDetail) {
                $bahanBakuDetail->decrement('stok', $detail->jumlah_digunakan);
            }
        });
    }
}
