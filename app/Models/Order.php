<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'no_transaksi',
        'nama_customer',
        'users_id',
        'tanggal_order',
        'status_transaksi',
        'status_pembayaran',
        'subtotal',
        'total_harga',
        'jumlah_bayar',
        'sisa_bayar',
        'kembalian',
    ];

    protected $casts = [
        'tanggal_order' => 'date',
    ];

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
            $order->status_transaksi = $order->status_transaksi ?? 'proses';
            $order->status_pembayaran = $order->status_pembayaran ?? 'belum_bayar';
            $order->tanggal_order = $order->tanggal_order ?? now();

            if (empty($order->no_transaksi)) {
                $prefix = 'INV/' . now()->format('Y/m') . '/';
                $last = static::where('no_transaksi', 'like', $prefix . '%')
                    ->orderByDesc('no_transaksi')
                    ->value('no_transaksi');
                $next = $last ? ((int) substr($last, -4)) + 1 : 1;
                $order->no_transaksi = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            }
        });

        static::saving(function ($order) {
            // Calculate total bayar from order details
            $order->total_harga = $order->orderDetails->sum('subtotal');
        });

        static::saved(function ($order) {
            // Create payment record if jumlah_bayar is set
            if ($order->jumlah_bayar) {
                // Jika ada kembalian, jumlah_bayar yang disimpan adalah total_harga
                $jumlahBayar = $order->kembalian > 0 ? $order->total_harga : $order->jumlah_bayar;
                
                $order->payments()->create([
                    'jumlah_bayar' => $jumlahBayar,
                    'sisa_bayar' => $order->sisa_bayar,
                ]);
            }
        });
    }
}
