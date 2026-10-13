<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StockPresentation
{
    public static function quantity(float|int|string|null $stock, ?string $unit = null): string
    {
        if ($stock === null) {
            return 'Belum tercatat';
        }

        $quantity = rtrim(rtrim(number_format((float) $stock, 2, ',', '.'), '0'), ',');
        $unit = filled($unit) ? mb_strtolower(trim($unit)) : 'satuan belum diatur';

        return $quantity . ' ' . $unit;
    }

    public static function minimum(Model $record): ?float
    {
        $minimum = $record->getAttributes()['stok_minimal'] ?? null;

        return $minimum !== null && (float) $minimum > 0 ? (float) $minimum : null;
    }

    public static function status(float|int|string|null $stock, float|int|string|null $minimum = null): string
    {
        if ($stock === null) {
            return 'Belum tercatat';
        }

        return match (true) {
            (float) $stock < 0 => 'Perlu diperiksa',
            (float) $stock === 0.0 => 'Habis',
            $minimum !== null && (float) $minimum > 0 && (float) $stock <= (float) $minimum => 'Stok rendah',
            default => 'Tersedia',
        };
    }

    public static function color(float|int|string|null $stock, float|int|string|null $minimum = null): string
    {
        return match (self::status($stock, $minimum)) {
            'Perlu diperiksa', 'Habis' => 'danger',
            'Stok rendah' => 'warning',
            'Tersedia' => 'success',
            default => 'gray',
        };
    }

    public static function statusOptions(string $table): array
    {
        $options = [
            'tersedia' => 'Tersedia',
            'habis' => 'Habis',
            'perlu_diperiksa' => 'Perlu diperiksa',
        ];

        if (Schema::hasColumn($table, 'stok_minimal')) {
            $options['rendah'] = 'Stok rendah';
        }

        return $options;
    }

    public static function filter(Builder $query, ?string $status): Builder
    {
        if (blank($status)) {
            return $query;
        }

        $hasMinimum = Schema::hasColumn($query->getModel()->getTable(), 'stok_minimal');

        return match ($status) {
            'habis' => $query->where('stok', 0),
            'perlu_diperiksa' => $query->where('stok', '<', 0),
            'rendah' => $hasMinimum
                ? $query->where('stok', '>', 0)->where('stok_minimal', '>', 0)->whereColumn('stok', '<=', 'stok_minimal')
                : $query->whereRaw('1 = 0'),
            'tersedia' => $query->where('stok', '>', 0)->when($hasMinimum, fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->whereNull('stok_minimal')->orWhere('stok_minimal', '<=', 0)->orWhereColumn('stok', '>', 'stok_minimal'),
            )),
            default => $query,
        };
    }
}
