<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditProduksi extends EditRecord
{
    protected static string $resource = ProduksiResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Ubah Rencana Produksi';
    }

    public function getSubheading(): string
    {
        return 'Rencana dapat disesuaikan sebelum produksi dimulai. Stok bahan belum berubah.';
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $field = $field === 'status' ? 'jumlah_produksi' : $field;
                $errors[str_starts_with($field, 'data.') ? $field : 'data.'.(in_array($field, ['jumlah_digunakan', 'bahan_baku_id'], true) ? 'produksiDetail' : $field)] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make()->label('Lihat rincian produksi')];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Simpan rencana');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->label('Kembali ke rincian')->alpineClickHandler(null)
            ->url($this->getResource()::getUrl('view', ['record' => $this->record]));
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Rencana produksi diperbarui';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
