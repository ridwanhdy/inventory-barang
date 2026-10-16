<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBahanBakus extends ListRecords
{
    protected static string $resource = BahanBakuResource::class;

    protected ?string $heading = 'Bahan Baku';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Kelola identitas, kategori, dan satuan bahan yang digunakan dalam penerimaan serta produksi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Bahan Baku'),
        ];
    }
}
