<?php

namespace App\Providers;

use App\Models;
use App\Policies\FilamentResourcePolicy;
use App\Policies\InventoryStockPolicy;
use App\Policies\SalesPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Models\User::class => FilamentResourcePolicy::class,
        Models\Product::class => FilamentResourcePolicy::class,
        Models\BahanBaku::class => FilamentResourcePolicy::class,
        Models\Kategori::class => FilamentResourcePolicy::class,
        Models\Satuan::class => FilamentResourcePolicy::class,
        Models\Pemasok::class => FilamentResourcePolicy::class,
        Models\Pembelian::class => FilamentResourcePolicy::class,
        Models\PembelianDetail::class => FilamentResourcePolicy::class,
        Models\Produksi::class => FilamentResourcePolicy::class,
        Models\ProduksiDetail::class => FilamentResourcePolicy::class,
        Models\BahanBakuHistory::class => FilamentResourcePolicy::class,
        Models\ProductDetail::class => InventoryStockPolicy::class,
        Models\BahanBakuDetail::class => InventoryStockPolicy::class,
        Models\Order::class => SalesPolicy::class,
        Models\OrderDetail::class => SalesPolicy::class,
        Models\Payment::class => SalesPolicy::class,
        Models\PaymentDetail::class => SalesPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
