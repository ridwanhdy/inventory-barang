<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            if (isset($exception->errors()['quantity'])) {
                throw ValidationException::withMessages(['data.pembelianDetails' => $exception->errors()['quantity']]);
            }

            throw $exception;
        }
    }

    public function getTitle(): string
    {
        return 'Koreksi Penerimaan Bahan Baku';
    }

    public function getSubheading(): string
    {
        return 'Perubahan jumlah atau bahan akan menyesuaikan selisih stok. Koreksi yang membuat stok bahan baku negatif tidak dapat disimpan.';
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Simpan koreksi');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali ke penerimaan')
            ->url($this->getResource()::getUrl('index'));
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Penerimaan dan stok bahan baku diperbarui';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Lihat penerimaan'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
