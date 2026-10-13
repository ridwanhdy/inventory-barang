<?php

namespace App\Support;

use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use App\Models\Kategori;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Pemasok;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use App\Models\Satuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MasterDataUsage
{
    public static function isUsed(Model $record): bool
    {
        if (! $record->exists) {
            return false;
        }

        $id = $record->getKey();

        return match (true) {
            $record instanceof Product => OrderDetail::query()->where('product_id', $id)->exists()
                || Produksi::query()->where('product_id', $id)->exists()
                || ProductDetail::query()->where('product_id', $id)->where('stok', '!=', 0)->exists(),
            $record instanceof BahanBaku => PembelianDetail::query()->where('bahan_baku_id', $id)->exists()
                || ProduksiDetail::query()->where('bahan_baku_id', $id)->exists()
                || BahanBakuDetail::query()->where('bahan_baku_id', $id)->where('stok', '!=', 0)->exists(),
            $record instanceof Pemasok => Pembelian::query()->where('pemasok_id', $id)->exists(),
            $record instanceof Satuan => Product::query()->where('satuan_id', $id)->exists()
                || BahanBaku::query()->where('satuan_id', $id)->exists(),
            $record instanceof Kategori => Product::query()->where('kategori_id', $id)->exists()
                || BahanBaku::query()->where('kategori_id', $id)->exists(),
            $record instanceof User => Order::query()->where('users_id', $id)->exists(),
            default => false,
        };
    }
}
