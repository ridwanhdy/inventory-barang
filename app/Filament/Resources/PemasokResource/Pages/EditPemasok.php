<?php

namespace App\Filament\Resources\PemasokResource\Pages;

use App\Filament\Resources\PemasokResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPemasok extends EditRecord
{
    protected static string $resource = PemasokResource::class;

    protected static ?string $title = 'Ubah pemasok';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Perbarui nama, nomor telepon, dan alamat agar informasi pemasok tetap mudah dihubungi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat detail'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
