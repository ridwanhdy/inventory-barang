<?php

namespace App\Filament\Resources\ProduksiResource\Pages;

use App\Filament\Resources\ProduksiResource;
use App\Models\Produksi;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateProduksi extends CreateRecord
{
    protected static string $resource = ProduksiResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected static bool $canCreateAnother = false;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Buat Rencana Produksi';
    }

    public function getSubheading(): string
    {
        return 'Simpan rencana beserta kebutuhan bahan, lalu mulai produksi dari halaman rincian ketika bahan siap.';
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            if ($this->record && ! $this->record->fresh()) {
                $repeater = $this->form->getComponent(fn ($component): bool => $component instanceof Repeater && $component->getName() === 'produksiDetail');
                $this->record = null;
                $this->form->model(Produksi::class);
                $repeater?->clearCachedExistingRecords();
            }
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors[str_starts_with($field, 'data.') ? $field : 'data.'.(in_array($field, ['jumlah_digunakan', 'bahan_baku_id'], true) ? 'produksiDetail' : $field)] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Simpan rencana');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->label('Kembali ke produksi')->alpineClickHandler(null)->url($this->getResource()::getUrl('index'));
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Rencana produksi disimpan. Stok bahan belum berubah.';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
