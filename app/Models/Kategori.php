<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    protected $fillable = [
        'nama_kategori',
    ];

    public function bahanBakus()
    {
        return $this->hasMany(BahanBaku::class);
    }

    public function product()
    {
        return $this->hasMany(Product::class);
    }
}
