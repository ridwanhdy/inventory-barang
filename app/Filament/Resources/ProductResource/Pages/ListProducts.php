<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Kelola katalog produk, varian ukuran, warna, bahan, serta harga jual untuk kebutuhan produksi dan penjualan.';
    }

    public function boot(): void
    {
        app()->setLocale('id');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Produk'),
        ];
    }
}
