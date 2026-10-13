<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected static bool $canCreateAnother = false;

    public function boot(): void { app()->setLocale('id'); }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $exception) {
            if ($this->record !== null && $this->record->fresh() === null) {
                $this->record = null;
                $this->form->model(Order::class);
                foreach ($this->form->getFlatComponents() as $component) {
                    if ($component instanceof Repeater) {
                        $component->clearCachedExistingRecords();
                    }
                }
            }
            Notification::make()->title('Penjualan belum disimpan')
                ->body(collect($exception->errors())->flatten()->first())->danger()->send();
            $errors = $exception->errors();
            if (isset($errors['orderDetails'])) {
                $errors['data.orderDetails'] = $errors['orderDetails'];
            }
            throw ValidationException::withMessages($errors);
        }
    }

    public function getSubheading(): ?string
    {
        return 'Catat produk yang keluar. Setelah disimpan, lanjutkan pembayaran pelanggan.';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['users_id'] = auth()->id();
        $data['status_transaksi'] = 'proses';
        unset($data['status_pembayaran']);

        return $data;
    }

    protected function afterCreate(): void { $this->getRecord()->refreshFinancials(); }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Simpan dan lanjut pembayaran'),
            $this->getCancelFormAction()->label('Kembali'),
        ];
    }
}
