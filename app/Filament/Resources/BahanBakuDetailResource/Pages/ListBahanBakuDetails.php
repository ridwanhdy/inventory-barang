<?php

namespace App\Filament\Resources\BahanBakuDetailResource\Pages;

use App\Filament\Resources\BahanBakuDetailResource;
use Filament\Resources\Pages\ListRecords;

class ListBahanBakuDetails extends ListRecords
{
    protected static string $resource = BahanBakuDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Remove create button - BahanBakuDetail is created automatically via observer
        ];
    }
} 