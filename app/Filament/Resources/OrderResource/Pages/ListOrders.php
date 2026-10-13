<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
    public function boot(): void { app()->setLocale('id'); }

    public function getSubheading(): ?string
    {
        return 'Penjualan kaos dan jaket kepada pelanggan. Pantau barang keluar, pembayaran, dan sisa tagihan.';
    }

    public function getBreadcrumb(): ?string { return 'Daftar penjualan'; }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Buat penjualan')];
    }
}
