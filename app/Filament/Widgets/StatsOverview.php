<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Produksi;
use App\Models\BahanBaku;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();
        
        return [
            Stat::make('Total Penjualan Hari Ini', 
                'Rp ' . number_format(Order::whereDate('created_at', $today)->sum('total_harga'), 0, ',', '.'))
                ->description('Pendapatan hari ini')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Produksi Hari Ini',
                Produksi::whereDate('produksi_mulai', $today)->sum('jumlah_produksi') . ' pcs')
                ->description('Jumlah produksi hari ini')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),

            // Stat::make('Bahan Baku Hampir Habis',
            //     BahanBaku::whereColumn('stok', '<=', 'stok_minimal')->count() . ' item')
            //     ->description('Perlu restock')
            //     ->descriptionIcon('heroicon-o-exclamation-triangle')
            //     ->color('danger'),
        ];
    }
} 