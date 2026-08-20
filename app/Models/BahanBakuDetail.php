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

    protected $casts = [
        'stok' => 'float',
        'stok_minimal' => 'float',
    ];

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }
} 