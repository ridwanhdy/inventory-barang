<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\TableWidget;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestOrders extends TableWidget
{
    protected static ?string $heading = 'Order Terbaru';
    

    protected function getTableQuery(): Builder
    {
        return Order::query()->latest()->take(5);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('customer.nama')
                ->label('Customer')
                ->searchable(),
            TextColumn::make('total_harga')
                ->label('Total')
                ->money('IDR'),
            TextColumn::make('status_pembayaran')
                ->label('Status Pembayaran')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'lunas' => 'success',
                    'cicilan' => 'warning',
                    'belum_bayar' => 'danger',
                }),
            TextColumn::make('created_at')
                ->label('Tanggal')
                ->dateTime(),
        ];
    }
}
