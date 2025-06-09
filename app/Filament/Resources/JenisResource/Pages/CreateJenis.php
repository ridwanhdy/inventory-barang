<?php

namespace App\Filament\Resources\JenisResource\Pages;

use App\Filament\Resources\JenisResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateJenis extends CreateRecord
{
    protected static string $resource = JenisResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Tambah'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label("Tambah & Tambah Lainnya")] : []),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
