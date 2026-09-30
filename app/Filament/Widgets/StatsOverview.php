<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Support\DashboardMetrics;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $months = DashboardMetrics::monthlySummary();
        $current = $months[array_key_last($months)];
        $previous = $months[count($months) - 2];
        $salesTrend = $this->trend($current['sales'], $previous['sales']);
        $ordersTrend = $this->trend($current['orders'], $previous['orders']);

        $stocks = Product::query()->select(['id'])->withSum('details', 'stok')->get();
        $availableProducts = $stocks->filter(fn (Product $product): bool => (float) $product->details_sum_stok > 0)->count();
        $emptyProducts = $stocks->count() - $availableProducts;
        $remaining = DashboardMetrics::outstandingPayments();

        return [
            Stat::make('Penjualan bulan ini', $this->rupiah($current['sales']))
                ->icon('heroicon-o-banknotes')
                ->description($salesTrend['description'])
                ->descriptionIcon($salesTrend['icon'])
                ->descriptionColor($salesTrend['color'])
                ->chart(array_column($months, 'sales'))
                ->color('success')
                ->extraAttributes(['class' => 'inv-stat inv-stat--sales']),

            Stat::make('Order bulan ini', number_format($current['orders'], 0, ',', '.'))
                ->icon('heroicon-o-shopping-bag')
                ->description($ordersTrend['description'])
                ->descriptionIcon($ordersTrend['icon'])
                ->descriptionColor($ordersTrend['color'])
                ->chart(array_column($months, 'orders'))
                ->color('info')
                ->extraAttributes(['class' => 'inv-stat inv-stat--orders']),

            Stat::make('Produk tersedia', number_format($availableProducts, 0, ',', '.'))
                ->icon('heroicon-o-cube')
                ->description($stocks->isEmpty() ? 'Belum ada produk' : ($emptyProducts > 0 ? number_format($emptyProducts, 0, ',', '.').' produk kehabisan stok' : 'Semua produk memiliki stok'))
                ->descriptionIcon($emptyProducts > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($emptyProducts > 0 ? 'warning' : 'success')
                ->extraAttributes(['class' => 'inv-stat inv-stat--stock']),

            Stat::make('Sisa pembayaran', $this->rupiah($remaining['amount']))
                ->icon('heroicon-o-wallet')
                ->description($remaining['orders'] > 0 ? number_format($remaining['orders'], 0, ',', '.').' order belum lunas' : 'Belum ada tagihan')
                ->descriptionIcon($remaining['orders'] > 0 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                ->color($remaining['orders'] > 0 ? 'warning' : 'success')
                ->extraAttributes(['class' => 'inv-stat inv-stat--payment']),
        ];
    }

    private function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    private function trend(float $current, float $previous): array
    {
        if ($previous <= 0) {
            return [
                'description' => $current > 0 ? 'Mulai tumbuh bulan ini' : 'Belum ada transaksi bulan ini',
                'icon' => $current > 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-minus',
                'color' => $current > 0 ? 'success' : 'gray',
            ];
        }

        $difference = (($current - $previous) / $previous) * 100;

        return [
            'description' => ($difference > 0 ? '+' : '').number_format($difference, 1, ',', '.').'% dari bulan lalu',
            'icon' => $difference > 0 ? 'heroicon-o-arrow-trending-up' : ($difference < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus'),
            'color' => $difference > 0 ? 'success' : ($difference < 0 ? 'danger' : 'gray'),
        ];
    }
}
