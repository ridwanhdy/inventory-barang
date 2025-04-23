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
            $bahan = $detail->bahanBaku;

            if ($bahan->stok < $detail->jumlah_digunakan) {
                Notification::make()
                    ->title('Stok Bahan Baku Tidak Cukup')
                    ->danger()
                    ->send();

                return false; // Membatalkan proses penyimpanan
            }

            // Jika cukup, kurangi stok
            $bahan->decrement('stok', $detail->jumlah_digunakan);
        });
    }
}
