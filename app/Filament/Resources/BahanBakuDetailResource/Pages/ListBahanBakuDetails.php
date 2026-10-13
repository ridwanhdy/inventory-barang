<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\PembelianResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListBahanBakuDetails extends ListRecords
{
    protected static string $resource = BahanBakuDetailResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): ?string
    {
        return 'Jumlah bahan yang tersedia untuk produksi.';
    }

    public function getBreadcrumb(): ?string
    {
        return 'Daftar stok';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bahan_masuk')
                ->label('Catat bahan masuk')
                ->icon('heroicon-o-plus')
                ->url(PembelianResource::getUrl('create'))
                ->visible(fn () => PembelianResource::canCreate()),
        ];
    }
}
