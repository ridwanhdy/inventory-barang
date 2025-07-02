<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'jumlah_bayar',
        'sisa_bayar',
        'kembalian',
        'metode_pembayaran',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'jumlah_bayar' => 'integer',
        'sisa_bayar' => 'integer',
        'kembalian' => 'integer',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
