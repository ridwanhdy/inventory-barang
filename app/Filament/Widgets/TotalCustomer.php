<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TotalCustomer extends ChartWidget
{
    protected static ?string $heading = 'Total Customer per Bulan';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Customer::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        $customers = [];

        foreach ($data as $item) {
            $months[] = Carbon::create()->month($item->month)->format('F');
            $customers[] = $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Customer',
                    'data' => $customers,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => '#3B82F6',
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
