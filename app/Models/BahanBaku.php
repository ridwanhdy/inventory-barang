<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBaku extends Model
{
    protected $fillable = [
        'nama_bahan',
        'satuan_id',
        'kategori_id',
        'jenis',
    ];

    public function details()
    {
        return $this->hasMany(BahanBakuDetail::class);
    }

    public function bahanBakuDetails()
    {
        return $this->hasMany(BahanBakuDetail::class, 'bahan_baku_id');
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }
}
