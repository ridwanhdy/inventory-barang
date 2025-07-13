<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pemasok extends Model
{
    protected $fillable = [
        'nama_pemasok',
        'nomor_telepon',
        'alamat',
    ];

    public function bahanBakus(): HasMany
    {
        return $this->hasMany(BahanBaku::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
