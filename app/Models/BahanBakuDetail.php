<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBakuDetail extends Model
{
    protected $fillable = [
        'bahan_baku_id',
        'stok',
        'stok_minimal',
    ];

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }
} 