<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduksi extends EditRecord
{
    protected static string $resource = ProduksiResource::class;

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
}
