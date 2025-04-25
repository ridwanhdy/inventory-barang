<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'users_id',
        'status_transaksi',
        'status_pembayaran',
        'tanggal_order',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            // Set default values if not provided
            $order->status_transaksi = $order->status_transaksi ?? 'proses';
            $order->status_pembayaran = $order->status_pembayaran ?? 'belum_bayar';
            $order->tanggal_order = $order->tanggal_order ?? now();
        });
    }
}
