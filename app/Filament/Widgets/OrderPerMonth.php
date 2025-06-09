<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrderPerMonth extends ChartWidget
{
    protected static ?string $heading = 'Jumlah Order per Bulan';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Order::selectRaw('MONTH(tanggal_order) as month, COUNT(*) as total')
            ->whereYear('tanggal_order', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $orders = [];

        foreach ($data as $item) {
            $months[] = Carbon::create()->month($item->month)->format('F');
            $orders[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Order',
                    'data' => $orders,
                    'borderColor' => '#10B981',
                    'backgroundColor' => '#10B981',
                    'tension' => 0.3,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
