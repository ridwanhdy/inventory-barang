<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\ProduksiResource;
use App\Models\BahanBakuDetail;
use App\Models\Produksi;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryHealth extends Widget
{
    protected static string $view = 'filament.widgets.inventory-health';

    protected static ?int $sort = 3;

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = ['default' => 1, 'xl' => 1];

    public static function canView(): bool
    {
        return auth()->check() && BahanBakuDetailResource::canViewAny();
    }

    protected function getViewData(): array
    {
        $hasMinimum = Schema::hasColumn('bahan_baku_details', 'stok_minimal');

        // A material may have several stock entries; monitor its combined stock.
        $stockQuery = BahanBakuDetail::query()
            ->whereHas('bahanBaku')
            ->selectRaw('MIN(id) AS id, bahan_baku_id, SUM(stok) AS stok')
            ->groupBy('bahan_baku_id');

        if ($hasMinimum) {
            $stockQuery->selectRaw('MAX(stok_minimal) AS stok_minimal');
        } else {
            $stockQuery->selectRaw('0 AS stok_minimal');
        }

        $summary = DB::query()
            ->fromSub((clone $stockQuery)->toBase(), 'monitored_stock')
            ->selectRaw('COUNT(*) AS total, COUNT(CASE WHEN stok <= stok_minimal THEN 1 END) AS low_stock')
            ->first();

        return [
            'hasMinimum' => $hasMinimum,
            'totalMaterials' => (int) $summary->total,
            'lowStockCount' => (int) $summary->low_stock,
            'materials' => $stockQuery
                ->havingRaw($hasMinimum ? 'SUM(stok) <= MAX(stok_minimal)' : 'SUM(stok) <= 0')
                ->with('bahanBaku.satuan')
                ->orderBy('stok')
                ->orderBy('bahan_baku_id')
                ->limit(3)
                ->get(),
            'productionCount' => ProduksiResource::canViewAny()
                ? Produksi::query()->where('status', 'Proses')->count()
                : null,
            'stockUrl' => BahanBakuDetailResource::canViewAny()
                ? BahanBakuDetailResource::getUrl('index')
                : null,
        ];
    }

    public function formatQuantity(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 2, ',', '.'), '0'), ',');
    }
}
