<?php

namespace App\Filament\Resources\JenisResource\Pages;

use App\Filament\Resources\JenisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJenis extends EditRecord
{
    protected static string $resource = JenisResource::class;

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
