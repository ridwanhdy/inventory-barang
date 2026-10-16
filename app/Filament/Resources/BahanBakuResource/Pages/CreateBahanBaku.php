<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBahanBaku extends CreateRecord
{
    protected static string $resource = BahanBakuResource::class;

    protected static ?string $title = 'Tambah bahan baku';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Daftarkan bahan beserta kategori dan satuannya sebelum mencatat penerimaan atau produksi.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['jenis'] = 'bahan baku';

        return $data;
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
