<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKategoris extends ListRecords
{
    protected static string $resource = KategoriResource::class;

    protected ?string $heading = 'Kategori';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kategori'),
        ];
    }
}
