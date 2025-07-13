<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use Filament\Resources\Pages\EditRecord;

class EditBahanBakuDetail extends EditRecord
{
    protected static string $resource = BahanBakuDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 