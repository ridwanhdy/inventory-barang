<?php

namespace App\Filament\Resources\SatuanResource\Pages;

use App\Filament\Resources\SatuanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSatuan extends CreateRecord
{
    protected static string $resource = SatuanResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Tambah Satuan';
    }

    public function getSubheading(): string
    {
        return 'Tambahkan satuan yang akan dipakai pada bahan baku dan produk, seperti meter, kilogram, atau pcs.';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Satuan berhasil ditambahkan';
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Tambah'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label('Tambah & Tambah Lainnya')] : []),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
