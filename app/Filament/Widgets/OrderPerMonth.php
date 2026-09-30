<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class OrderPerMonth extends ChartWidget
{
    protected static ?string $heading = 'Tren penjualan';

    protected static ?string $description = 'Nilai order selesai dalam 6 bulan terakhir';

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected static ?string $maxHeight = '260px';

    protected static ?string $pollingInterval = '60s';

    protected int|string|array $columnSpan = 1;

    protected function getData(): array
    {
        $months = DashboardMetrics::monthlySummary();

        return [
            'datasets' => [
                [
                    'label' => 'Penjualan',
                    'data' => array_column($months, 'sales'),
                    'borderColor' => '#7EC151',
                    'backgroundColor' => 'rgba(196, 247, 202, 0.35)',
                    'pointBackgroundColor' => '#92EEFF',
                    'pointBorderColor' => '#7EC151',
                    'fill' => true,
                    'tension' => 0.4,
                    'borderWidth' => 3,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => array_column($months, 'label'),
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => 'Penjualan: ' + new Intl.NumberFormat('id-ID', {
                            style: 'currency', currency: 'IDR', maximumFractionDigits: 0
                        }).format(context.parsed.y)
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: {
                        maxTicksLimit: 5,
                        callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID', {
                            notation: 'compact', maximumFractionDigits: 1
                        }).format(value)
                    }
                }
            }
        }
        JS);
    }

    protected function getType(): string
    {
        return 'line';
    }
}
