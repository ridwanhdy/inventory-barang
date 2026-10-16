<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Tambahkan identitas produk, varian, harga jual per satuan, dan foto produk bila tersedia.';
    }

    public function boot(): void
    {
        app()->setLocale('id');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['foto'] = $data['foto'] ?? '';

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Tambah produk'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label('Tambah & tambah lainnya')] : []),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
}
