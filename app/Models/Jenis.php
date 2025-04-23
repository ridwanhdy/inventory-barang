<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jenis extends Model
{
    protected $fillable = [
        'nama_jenis',
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
