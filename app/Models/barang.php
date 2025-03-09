<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class barang extends Model
{
    protected $fillable = ['nama_barang', 'jenis_id', 'satuan_id', 'stok', 'stok_minimum', 'kategori'];

    public function jenis()
    {
        return $this->belongsTo(Jenis::class);
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }
    protected $casts = [
        'stok' => 'float',
        'stok_minimum' => 'float',
    ];
}
