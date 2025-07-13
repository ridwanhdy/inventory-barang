<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'nama_product',
        'kategori_id',
        'satuan_id',
        'ukuran',
        'warna',
        'bahan',
        'harga_jual',
        'foto',
    ];

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

    public function details()
    {
        return $this->hasMany(ProductDetail::class);
    }
}
