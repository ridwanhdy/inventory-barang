<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan')
                ->submit('save')
                ->keyBindings(['mod+s']),
            Actions\Action::make('cancel')
                ->label('Batal')
                ->url(fn () => $this->getResource()::getUrl())
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['jenis_id'] = 2; // ganti dengan id jenis bahan baku
    return $data;
}

}
