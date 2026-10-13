<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;
use Illuminate\Validation\ValidationException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;
    protected static ?string $title = 'Detail dan pembayaran penjualan';

    public function boot(): void { app()->setLocale('id'); }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $exception) {
            Notification::make()->title('Koreksi penjualan belum disimpan')
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
        return 'Catat pembayaran melalui tombol Bayar. Tandai barang diserahkan setelah diterima pelanggan.';
    }

    protected function afterSave(): void { $this->getRecord()->refreshFinancials(); }

    #[On('sale-payment-recorded')]
    public function paymentRecorded(int $orderId): void
    {
        if ($this->getRecord()->id === $orderId) {
            $this->getRecord()->refresh();
            $this->refreshFormData(['status_transaksi', 'status_pembayaran']);
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['users_id'], $data['status_transaksi'], $data['status_pembayaran']);

        return $data;
    }

    protected function savePendingCorrections(): void
    {
        if ($this->getRecord()->fresh()->canEditItems()) {
            $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
            $this->getRecord()->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('bayar')->label('Bayar')->icon('heroicon-o-banknotes')
                ->visible(fn (): bool => $this->getRecord()->fresh()->status_transaksi !== 'batal' && $this->getRecord()->remainingAmount() > 0)
                ->action(function (): void {
                    $this->savePendingCorrections();
                    $this->dispatch('open-sale-payment', orderId: $this->getRecord()->id);
                }),
            Actions\Action::make('serahkan_barang')->label('Barang sudah diserahkan')
                ->icon('heroicon-o-check-circle')->color('success')
                ->visible(fn (): bool => $this->getRecord()->fresh()->status_transaksi === 'proses')
                ->requiresConfirmation()->modalHeading('Konfirmasi penyerahan barang')
                ->modalDescription('Tandai selesai setelah produk diterima pelanggan. Pembayaran tetap dapat dicatat jika masih ada tagihan.')
                ->action(function (): void {
                    $this->savePendingCorrections();
                    $this->getRecord()->update(['status_transaksi' => 'selesai']);
                    $this->refreshFormData(['status_transaksi', 'status_pembayaran']);
                    Notification::make()->title('Barang ditandai sudah diserahkan')->success()->send();
                }),
            Actions\Action::make('print-struk')->label('Cetak struk')->icon('heroicon-o-printer')->color('gray')
                ->url(fn (): string => route('order.print-struk', $this->getRecord()))->openUrlInNewTab(),
            Actions\Action::make('batalkan_penjualan')->label('Batalkan penjualan')->color('danger')
                ->visible(fn (): bool => $this->getRecord()->fresh()->status_transaksi === 'proses')
                ->disabled(fn (): bool => ! $this->getRecord()->fresh()->canCancel())
                ->tooltip('Pembatalan hanya tersedia sebelum pembayaran dan penyerahan barang.')
                ->requiresConfirmation()->modalHeading('Batalkan penjualan?')
                ->modalDescription('Produk dikembalikan ke stok. Riwayat penjualan tetap tersimpan.')
                ->action(function (): void {
                    $this->getRecord()->cancelSale();
                    $this->fillForm();
                    Notification::make()->title('Penjualan dibatalkan dan stok dikembalikan')->success()->send();
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label('Simpan koreksi')
                ->visible(fn (): bool => $this->getRecord()->fresh()->canEditItems()),
            $this->getCancelFormAction()->label('Kembali ke penjualan'),
        ];
    }
}
