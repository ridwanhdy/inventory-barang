<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Produksi extends Model
{
    protected $fillable = ['nama_barang_id', 'jumlah_produksi', 'bahan_baku_1_id', 'bahan_baku_2_id', 'status'];

    protected static function boot()
    {
        parent::boot();

        // Saat Submit (Save), kurangi stok bahan baku
        static::creating(function ($produksi) {
            $produksi->kurangiStokBahanBaku();
        });

        // Saat Update, cek apakah status berubah menjadi "Selesai"
        static::updating(function ($produksi) {
            if ($produksi->isDirty('status') && $produksi->status === 'Selesai') {
                $produksi->tambahStokBarangJadi();
            }
        });
    }

    private function kurangiStokBahanBaku()
    {
        $bahanBaku1 = Barang::find($this->bahan_baku_1_id);
        $bahanBaku2 = Barang::find($this->bahan_baku_2_id);

        if ($bahanBaku1 && $bahanBaku1->stok >= $this->jumlah_produksi) {
            $bahanBaku1->stok -= $this->jumlah_produksi;
            $bahanBaku1->save();
        } else {
            throw new \Exception("Stok bahan baku 1 tidak cukup!");
        }

        if ($bahanBaku2 && $bahanBaku2->stok >= $this->jumlah_produksi) {
            $bahanBaku2->stok -= $this->jumlah_produksi;
            $bahanBaku2->save();
        } else {
            throw new \Exception("Stok bahan baku 2 tidak cukup!");
        }
    }

    private function tambahStokBarangJadi()
    {
        $barangJadi = Barang::find($this->nama_barang_id);
        if ($barangJadi) {
            $barangJadi->stok += $this->jumlah_produksi;
            $barangJadi->save();
        }
    }
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
}
