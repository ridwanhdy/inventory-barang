<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesStock
{
    /** Positive changes return stock; negative changes reserve it for a sale. */
    public static function change(array $changes): void
    {
        $changes = array_filter($changes, fn ($quantity): bool => $quantity != 0);
        ksort($changes);
        DB::transaction(function () use ($changes): void {
            foreach ($changes as $productId => $quantity) {
                // Lock the master too, so an absent stock row cannot race another sale.
                $product = Product::query()->whereKey($productId)->lockForUpdate()->first();
                if (! $product) {
                    throw ValidationException::withMessages(['orderDetails' => 'Produk penjualan tidak tersedia.']);
                }
                $details = ProductDetail::query()->where('product_id', $productId)->orderBy('id')->lockForUpdate()->get();
                $available = (float) $details->sum('stok');
                if ($quantity < 0 && ($details->contains(fn ($detail): bool => $detail->stok < 0) || $available < -$quantity)) {
                    throw ValidationException::withMessages([
                        'orderDetails' => 'Stok '.$product->nama_product.' tidak mencukupi. Tersedia '.number_format(max(0, $available), 0, ',', '.').' unit.',
                    ]);
                }
                if ($quantity > 0) {
                    $detail = $details->first();
                    if ($detail) {
                        $detail->increment('stok', $quantity);
                    } else {
                        $details->push(ProductDetail::create(['product_id' => $productId, 'stok' => $quantity]));
                    }
                } else {
                    $needed = -$quantity;
                    foreach ($details as $detail) {
                        $take = min($needed, (float) $detail->stok);
                        if ($take > 0) {
                            $detail->decrement('stok', $take);
                            $needed -= $take;
                        }
                        if ($needed <= 0) {
                            break;
                        }
                    }
                }
                // ProductDetail is authoritative; products.stok is only a mirror.
                Product::query()->whereKey($productId)->update(['stok' => $details->sum('stok')]);
            }
        }, 3);
    }
}
