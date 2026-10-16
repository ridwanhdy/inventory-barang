<?php

namespace App\Filament\Resources\KategoriResource\Pages;

use App\Filament\Resources\KategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKategori extends EditRecord
{
    protected static string $resource = KategoriResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Ubah Kategori';
    }

    public function getSubheading(): string
    {
        return 'Perbarui nama kategori bahan baku dan produk. Kategori yang sedang digunakan tidak dapat dihapus.';
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Kategori berhasil diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus kategori')
                ->modalDescription('Kategori yang belum digunakan akan dihapus. Kategori yang digunakan bahan baku atau produk dilindungi dari penghapusan.')
                ->modalSubmitActionLabel('Ya, hapus kategori'),
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
