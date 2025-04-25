<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\TableWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MonthlyOrders extends TableWidget
{
    protected static ?string $heading = 'Order Per Bulan';

    protected function getTableQuery(): Builder
    {
        return Order::query()
            ->selectRaw('DATE_FORMAT(created_at, "%M %Y") as bulan, COUNT(*) as jumlah_order, SUM(total_harga) as total_pendapatan')
            ->groupBy('bulan')
            ->orderBy('created_at', 'desc')
            ->take(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('bulan')
                ->label('Bulan')
                ->sortable(),
            TextColumn::make('jumlah_order')
                ->label('Jumlah Order')
                ->sortable(),
            TextColumn::make('total_pendapatan')
                ->label('Total Pendapatan')
                ->money('IDR')
                ->sortable(),
        ];
    }

    public function getTableRecordKey(Model $record): string
    {
        return $record->bulan;
    }
} 