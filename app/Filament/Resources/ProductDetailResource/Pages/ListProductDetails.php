<?php

namespace App\Filament\Resources\ProductDetailResource\Pages;

use App\Filament\Resources\ProductDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductDetails extends ListRecords
{
    protected static string $resource = ProductDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Remove create button - ProductDetail is created automatically via observer
        ];
    }
}
