<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Satuan extends Model
{
    protected $fillable = [
        'nama_satuan',
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
