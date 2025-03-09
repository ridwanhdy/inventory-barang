<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produksi extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_barang_id', 
        'jumlah_produksi', 
        'bahan_baku_1_id', 
        'bahan_baku_2_id', 
        'status'
    ];

    public function barangJadi()
    {
        return $this->belongsTo(Barang::class, 'nama_barang_id');
    }

    public function bahanBaku1()
    {
        return $this->belongsTo(Barang::class, 'bahan_baku_1_id');
    }

    public function bahanBaku2()
    {
        return $this->belongsTo(Barang::class, 'bahan_baku_2_id');
    }
    protected static function booted()
{
    static::updated(function ($produksi) {
        // Pastikan hanya berjalan ketika status berubah menjadi "Selesai"
        if ($produksi->status === 'Selesai') {
            $barangJadi = Barang::find($produksi->nama_barang_id);
            
            if ($barangJadi) {
                $barangJadi->stok += (int) $produksi->jumlah_produksi;
                $barangJadi->save();
            }
        }
    });
}

}
