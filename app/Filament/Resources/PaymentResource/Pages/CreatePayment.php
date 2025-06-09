<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Tambah'),
            ...(static::canCreateAnother() ? [$this->getCreateAnotherFormAction()->label("Tambah & Tambah Lainnya")] : []),
            $this->getCancelFormAction()->label('Batal'),
        ];
    }
} 