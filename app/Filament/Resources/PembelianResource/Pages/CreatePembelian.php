<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Models\Pembelian;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePembelian extends CreateRecord
{
    protected static string $resource = PembelianResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            if ($this->record && ! $this->record->fresh()) {
                $repeater = $this->form->getComponent(fn ($component): bool => $component instanceof Repeater && $component->getName() === 'pembelianDetails');
                $this->record = null;
                $this->form->model(Pembelian::class);
                $repeater?->clearCachedExistingRecords();
            }

            if (isset($exception->errors()['quantity'])) {
                throw ValidationException::withMessages(['data.pembelianDetails' => $exception->errors()['quantity']]);
            }

            throw $exception;
        }
    }

    public function getTitle(): string
    {
        return 'Catat Penerimaan Bahan Baku';
    }

    public function getSubheading(): string
    {
        return 'Gunakan jumlah bahan baku yang benar-benar diterima. Stok akan bertambah setelah penerimaan disimpan.';
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Catat penerimaan');
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()->label('Catat dan tambah lagi');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali ke penerimaan')
            ->alpineClickHandler(null)
            ->url($this->getResource()::getUrl('index'));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Penerimaan bahan baku tercatat';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
