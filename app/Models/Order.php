<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'users_id',
        'tanggal_order',
        'status_transaksi',
        'status_pembayaran',
        'metode_pembayaran',
        'subtotal',
        'total_harga',
    ];

    protected $casts = [
        'tanggal_order' => 'date',
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentDetails()
    {
        return $this->hasManyThrough(PaymentDetail::class, Payment::class);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            // Set default values if not provided
            $order->status_transaksi = $order->status_transaksi ?? 'proses';
            $order->status_pembayaran = $order->status_pembayaran ?? 'belum_bayar';
            $order->tanggal_order = $order->tanggal_order ?? now();
        });

        static::saving(function ($order) {
            // Calculate total bayar from order details
            $order->total_harga = $order->orderDetails->sum('subtotal');
        });
    }
}
