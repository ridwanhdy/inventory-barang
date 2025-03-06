<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class barangmasuk extends Model
{
    protected $fillable = ['barang_id', 'satuan_id','jumlah_masuk', 'tanggal_masuk'];

    public static function boot()
    {
        parent::boot();

        static::created(function ($barangMasuk) {
            // Tambahkan jumlah_masuk ke stok barang terkait
            $barang = $barangMasuk->barang;
            if ($barang) {
                $barang->stok += $barangMasuk->jumlah_masuk;
                $barang->save();
            }
        });
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
}
