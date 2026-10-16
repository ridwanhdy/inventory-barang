<?php

namespace App\Filament\Widgets;

use App\Support\DashboardMetrics;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class OrderPerMonth extends ChartWidget
{
    protected static ?string $heading = 'Tren penjualan';

    protected static ?string $description = 'Nilai penjualan selesai dalam 6 bulan terakhir';

    protected static ?int $sort = 2;

    protected static bool $isLazy = true;

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
                    'borderColor' => '#22396F',
                    'backgroundColor' => 'rgba(34, 57, 111, 0.08)',
                    'pointBackgroundColor' => '#FCF1D0',
                    'pointBorderColor' => '#22396F',
                    'pointBorderWidth' => 2,
                    'pointHoverBackgroundColor' => '#FCF1D0',
                    'pointHoverBorderColor' => '#010736',
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
                    backgroundColor: '#0D1C42',
                    titleColor: '#FCF1D0',
                    bodyColor: '#FCF1D0',
                    borderColor: '#22396F',
                    borderWidth: 1,
                    padding: 12,
                    callbacks: {
                        label: (context) => 'Penjualan: ' + new Intl.NumberFormat('id-ID', {
                            style: 'currency', currency: 'IDR', maximumFractionDigits: 0
                        }).format(context.parsed.y)
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: { color: '#5F6A80' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(13, 28, 66, 0.07)' },
                    border: { display: false },
                    ticks: {
                        color: '#5F6A80',
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
