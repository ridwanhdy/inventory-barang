<?php

namespace App\Observers;

use App\Models\ProductDetail;

class ProductDetailObserver
{
    /**
     * Handle the ProductDetail "created" event.
     */
    public function created(ProductDetail $productDetail): void
    {
        // No additional logic needed on creation for now
    }

    /**
     * Handle the ProductDetail "updated" event.
     */
    public function updated(ProductDetail $productDetail): void
    {
        //
    }

    /**
     * Handle the ProductDetail "deleted" event.
     */
    public function deleted(ProductDetail $productDetail): void
    {
        // No children to delete, but method is here for future use
    }

    /**
     * Handle the ProductDetail "restored" event.
     */
    public function restored(ProductDetail $productDetail): void
    {
        //
    }

    /**
     * Handle the ProductDetail "force deleted" event.
     */
    public function forceDeleted(ProductDetail $productDetail): void
    {
        //
    }
} 