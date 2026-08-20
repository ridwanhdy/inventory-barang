<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanBakuHistory extends Model
{
    protected $fillable = [
        'bahan_baku_id',
        'jumlah_perubahan',
        'tipe_perubahan',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_perubahan' => 'float',
    ];

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class);
    }
}
