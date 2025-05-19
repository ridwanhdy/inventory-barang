<?php

namespace App\Filament\Resources\BahanBakuResource\Pages;

use App\Filament\Resources\BahanBakuResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\TextInput;

class EditBahanBaku extends EditRecord
{
    protected static string $resource = BahanBakuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hapus kolom stok dari data yang akan disimpan
        unset($data['stok']);
        
        return $data;
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('nama_bahan')
                ->label('Nama Bahan')
                ->required(),

            TextInput::make('stok')
                ->label('Stok')
                ->disabled()
                ->dehydrated(false), // Mencegah field ini disimpan ke database

            TextInput::make('stok_minimal')
                ->label('Stok Minimal')
                ->required()
                ->numeric(),
        ];
    }
}
