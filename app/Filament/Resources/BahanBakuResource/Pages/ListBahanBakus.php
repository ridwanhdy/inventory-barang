<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBahanBakus extends ListRecords
{
    protected static string $resource = BahanBakuResource::class;

    protected ?string $heading = 'Bahan Baku';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Bahan Baku'),
        ];
    }
}
