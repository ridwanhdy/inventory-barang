<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Resources\Pages\CreateRecord;

class CreateKategori extends CreateRecord
{
    protected static string $resource = KategoriResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Tambah Kategori';
    }

    public function getSubheading(): string
    {
        return 'Buat kategori untuk mengelompokkan bahan baku dan produk agar pencatatan lebih teratur.';
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Kategori berhasil ditambahkan';
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
