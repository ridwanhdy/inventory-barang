<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'harga',
        'subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::creating(function ($orderDetail) {
            // Ensure order_id is set
            if (!$orderDetail->order_id) {
                throw new \Exception('Order ID is required for order detail');
            }
        });

        static::saving(function ($orderDetail) {
            // Calculate subtotal when saving
            $orderDetail->subtotal = $orderDetail->quantity * $orderDetail->harga;
        });
    }
} 