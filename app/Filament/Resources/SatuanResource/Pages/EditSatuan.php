<?php

namespace App\Filament\Resources\SatuanResource\Pages;

use App\Filament\Resources\SatuanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSatuan extends EditRecord
{
    protected static string $resource = SatuanResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Ubah Satuan';
    }

    public function getSubheading(): string
    {
        return 'Perbarui nama satuan agar pengukuran bahan baku dan produk seragam. Satuan yang sedang digunakan tidak dapat dihapus.';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Satuan berhasil diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus satuan')
                ->modalDescription('Satuan yang belum digunakan akan dihapus. Satuan yang digunakan bahan baku atau produk dilindungi dari penghapusan.')
                ->modalSubmitActionLabel('Ya, hapus satuan'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Simpan'),
            $this->getCancelFormAction()->label('Batal')
                ->alpineClickHandler(null)
                ->url($this->getResource()::getUrl()),
        ];
    }
}
