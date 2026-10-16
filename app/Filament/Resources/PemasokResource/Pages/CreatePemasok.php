<?php

namespace App\Filament\Resources\PemasokResource\Pages;

use App\Filament\Resources\PemasokResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePemasok extends CreateRecord
{
    protected static string $resource = PemasokResource::class;

    protected static ?string $title = 'Tambah pemasok';

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getSubheading(): string
    {
        return 'Daftarkan identitas dan kontak mitra yang menyediakan bahan baku untuk usaha Anda.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
