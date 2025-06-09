<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduksi extends CreateRecord
{
    protected static string $resource = ProduksiResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Tambah'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label("Tambah & Tambah Lainnya")] : []),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
