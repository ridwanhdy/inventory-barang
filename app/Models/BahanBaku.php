<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBaku extends Model
{
    protected $fillable = [
        'nama_bahan',
        'satuan_id',
        'kategori_id',
        'jenis_id',
        'stok',
        'stok_minimal',
    ];

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    // Relasi ke tabel Kategori (belongsTo)
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    // Relasi ke tabel Jenis (belongsTo)
    public function jenis()
    {
        return $this->belongsTo(Jenis::class);
    }

    public function histories()
    {
        return $this->hasMany(BahanBakuHistory::class);
    }
}
