<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductDetail;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Automatically create ProductDetail with stock 0
        ProductDetail::create([
            'product_id' => $product->id,
            'stok' => 0,
        ]);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Delete related ProductDetails when Product is deleted
        $product->details()->delete();
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
