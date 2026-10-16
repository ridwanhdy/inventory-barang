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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MasterDataUsage
{
    /**
     * Load the table's delete-button state with the records instead of querying per row.
     * Model deletion must continue to call isUsed() to check current database state.
     */
    public static function withUsage(Builder $query): Builder
    {
        $checks = static::usageQueries($query->getModel(), correlate: true);

        if ($checks === []) {
            return $query;
        }

        $expression = [];
        $bindings = [];

        foreach ($checks as $check) {
            $expression[] = 'exists ('.$check->selectRaw('1')->toSql().')';
            array_push($bindings, ...$check->getBindings());
        }

        return $query->addSelect([
            'master_data_is_used' => $query->getQuery()->newQuery()
                ->selectRaw(implode(' or ', $expression), $bindings),
        ]);
    }

    public static function isUsedForDisplay(Model $record): bool
    {
        if (array_key_exists('master_data_is_used', $record->getAttributes())) {
            return (bool) $record->getAttribute('master_data_is_used');
        }

        return static::isUsed($record);
    }

    public static function isUsed(Model $record): bool
    {
        if (! $record->exists) {
            return false;
        }

        foreach (static::usageQueries($record) as $check) {
            if ($check->exists()) {
                return true;
            }
        }

        return false;
    }

    /** @return list<Builder> */
    private static function usageQueries(Model $record, bool $correlate = false): array
    {
        $references = match (true) {
            $record instanceof Product => [[OrderDetail::class, 'product_id'], [Produksi::class, 'product_id'], [ProductDetail::class, 'product_id', true]],
            $record instanceof BahanBaku => [[PembelianDetail::class, 'bahan_baku_id'], [ProduksiDetail::class, 'bahan_baku_id'], [BahanBakuDetail::class, 'bahan_baku_id', true]],
            $record instanceof Pemasok => [[Pembelian::class, 'pemasok_id']],
            $record instanceof Satuan => [[Product::class, 'satuan_id'], [BahanBaku::class, 'satuan_id']],
            $record instanceof Kategori => [[Product::class, 'kategori_id'], [BahanBaku::class, 'kategori_id']],
            $record instanceof User => [[Order::class, 'users_id']],
            default => [],
        };

        return array_map(function (array $reference) use ($record, $correlate): Builder {
            [$model, $foreignKey] = $reference;
            $query = $model::query();

            $correlate
                ? $query->whereColumn($query->getModel()->qualifyColumn($foreignKey), $record->getQualifiedKeyName())
                : $query->where($foreignKey, $record->getKey());

            if ($reference[2] ?? false) {
                $query->where('stok', '!=', 0);
            }

            return $query;
        }, $references);
    }
}
