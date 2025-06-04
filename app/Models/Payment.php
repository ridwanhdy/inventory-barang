<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'jumlah_bayar',
        'sisa_bayar',
        'metode_pembayaran',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function paymentDetails()
    {
        return $this->hasMany(PaymentDetail::class);
    }
}
