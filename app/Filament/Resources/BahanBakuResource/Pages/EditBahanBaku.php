<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBahanBaku extends EditRecord
{
    protected static string $resource = BahanBakuResource::class;

    protected static ?string $title = 'Ubah bahan baku';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Perbarui identitas bahan. Pastikan satuan tetap sesuai dengan pencatatan penerimaan dan produksi yang sudah ada.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat detail'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
