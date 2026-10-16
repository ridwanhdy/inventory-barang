<?php

namespace App\Filament\Resources\SatuanResource\Pages;

use App\Filament\Resources\SatuanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSatuans extends ListRecords
{
    protected static string $resource = SatuanResource::class;

    protected ?string $heading = 'Satuan';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Kelola satuan pengukuran bahan baku dan produk agar pencatatan jumlah dan stok tetap konsisten.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Satuan'),
        ];
    }
}
