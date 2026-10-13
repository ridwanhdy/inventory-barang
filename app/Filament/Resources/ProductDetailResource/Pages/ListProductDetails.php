<?php

namespace App\Filament\Resources\ProductDetailResource\Pages;

use App\Filament\Resources\ProductDetailResource;
use App\Filament\Resources\ProduksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductDetails extends ListRecords
{
    protected static string $resource = ProductDetailResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): ?string
    {
        return 'Jumlah produk jadi yang siap dijual.';
    }

    public function getBreadcrumb(): ?string
    {
        return 'Daftar stok';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('produksi')
                ->label('Buka produksi')
                ->icon('heroicon-o-cog')
                ->url(ProduksiResource::getUrl('index'))
                ->visible(fn () => ProduksiResource::canViewAny()),
        ];
    }
}
