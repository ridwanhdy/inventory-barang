<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Models\Product;
use App\Models\BahanBaku;
use App\Models\ProductDetail;
use App\Models\Pemasok;
use App\Models\Satuan;
use App\Models\Kategori;
use App\Models\User;
use App\Support\MasterDataUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\BahanBakuObserver;
use App\Observers\ProductDetailObserver;

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
        ProductDetail::observe(ProductDetailObserver::class);

        foreach ([Product::class, BahanBaku::class, Pemasok::class, Satuan::class, Kategori::class, User::class] as $model) {
            $model::deleting(function (Model $record): void {
                if (MasterDataUsage::isUsed($record) || ($record instanceof User && auth()->user()?->is($record))) {
                    throw ValidationException::withMessages([
                        'master_data' => 'Data ini masih digunakan atau memiliki stok. Riwayat transaksi harus tetap tersimpan sehingga data tidak dapat dihapus.',
                    ]);
                }
            });
        }
    }
}
