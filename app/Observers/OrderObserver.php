<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Product;

class OrderObserver
{
    public function updated(Order $order)
    {
        // Check if status changed to 'selesai'
        if ($order->isDirty('status_transaksi') && $order->status_transaksi === 'selesai') {
            // Get all order details
            foreach ($order->orderDetails as $detail) {
                // Get the product
                $product = Product::find($detail->product_id);
                
                if ($product) {
                    // Update product stock
                    $product->stok -= $detail->quantity;
                    $product->save();
                }
            }
        }
    }
} 