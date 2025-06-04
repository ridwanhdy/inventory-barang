<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Order'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->url(fn ($record) => route('filament.admin.resources.orders.print', ['record' => $record]))
                ->openUrlInNewTab(),
        ];
    }
}
