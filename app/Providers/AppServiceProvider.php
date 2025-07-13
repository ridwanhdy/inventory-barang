<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\BahanBaku;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\BahanBakuObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        BahanBaku::observe(BahanBakuObserver::class);
    }
}
