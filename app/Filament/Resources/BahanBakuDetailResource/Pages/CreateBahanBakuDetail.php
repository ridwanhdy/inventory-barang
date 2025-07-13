<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBahanBakuDetail extends CreateRecord
{
    protected static string $resource = BahanBakuDetailResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 