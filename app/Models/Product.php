<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'nama_product',
        'jenis_id',
        'kategori_id',
        'satuan_id',
        'ukuran',
        'warna',
        'bahan',
        'stok',
        'harga_jual',
        'foto',
    ];

    public function jenis()
    {
        return $this->belongsTo(Jenis::class);
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function produksi()
    {
        return $this->hasMany(Produksi::class);
    }
}
