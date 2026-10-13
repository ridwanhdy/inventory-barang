<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Attributes\On;
use Illuminate\Validation\ValidationException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $title = 'Pembayaran penjualan';
    protected static bool $isLazy = false;

    #[On('open-sale-payment')]
    public function openSalePayment(int $orderId): void
    {
        if ((int) $this->getOwnerRecord()->getKey() !== $orderId) {
            return;
        }
        $order = $this->getOwnerRecord()->refresh();
        if ($order->status_transaksi !== 'batal' && $order->remainingAmount() > 0) {
            $this->mountTableAction('create');
        }
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('tagihan_sebelum_pembayaran')
                ->label('Sisa tagihan yang harus dibayar')
                ->content(fn (): string => $this->rupiah($this->getOwnerRecord()->remainingAmount()))
                ->columnSpanFull(),
            Forms\Components\TextInput::make('jumlah_bayar')
                ->label('Jumlah pembayaran')
                ->prefix('Rp')
                ->integer()
                ->minValue(1)
                ->maxValue(2147483647)
                ->required()
                ->live()
                ->helperText('Masukkan rupiah tanpa pemisah, misalnya 30000. Uang tunai yang melebihi sisa tagihan dihitung sebagai kembalian.'),
            Forms\Components\Select::make('metode_pembayaran')
                ->label('Metode pembayaran')
                ->options(['cash' => 'Tunai', 'bank' => 'Transfer bank'])
                ->default('cash')
                ->live()
                ->required(),
            Forms\Components\Placeholder::make('sisa_bayar')
                ->label('Sisa setelah pembayaran')
                ->content(fn (Forms\Get $get): string => $this->rupiah(max(0, $this->getOwnerRecord()->remainingAmount() - (int) $get('jumlah_bayar')))),
            Forms\Components\Placeholder::make('kembalian')
                ->label('Kembalian tunai')
                ->visible(fn (Forms\Get $get): bool => $get('metode_pembayaran') === 'cash')
                ->content(fn (Forms\Get $get): string => $this->rupiah(max(0, (int) $get('jumlah_bayar') - $this->getOwnerRecord()->remainingAmount()))),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('pembayaran')
            ->pluralModelLabel('pembayaran')
            // The custom header keeps Bayar on the parent page as the only entry point.
            // The create action stays registered so its modal can be opened after saving corrections.
            ->header(function () {
                $order = $this->getOwnerRecord()->fresh();
                return view('filament.resources.order-resource.relation-managers.payment-summary', [
                    'totalHarga' => $order->orderDetails()->sum('subtotal'),
                    'totalBayar' => $order->paidAmount(),
                    'sisaBayar' => $order->remainingAmount(),
                    'canPay' => $order->status_transaksi !== 'batal' && $order->remainingAmount() > 0,
                ]);
            })
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal pembayaran')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->formatStateUsing(fn (?string $state): string => $state === 'bank' ? 'Transfer bank' : 'Tunai'),
                Tables\Columns\TextColumn::make('jumlah_bayar')
                    ->label('Uang dibayar')->formatStateUsing(fn ($state): string => $this->rupiah($state)),
                Tables\Columns\TextColumn::make('kembalian')
                    ->label('Kembalian')->formatStateUsing(fn ($state): string => $this->rupiah($state)),
                Tables\Columns\TextColumn::make('diterima')
                    ->label('Pembayaran diterima')
                    ->getStateUsing(fn (Payment $record): int => $record->jumlah_bayar - $record->kembalian)
                    ->formatStateUsing(fn ($state): string => $this->rupiah($state)),
                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->label('Sisa saat dicatat')->formatStateUsing(fn ($state): string => $this->rupiah($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Catat pembayaran')
                    ->modalHeading('Catat pembayaran penjualan')
                    ->modalSubmitActionLabel('Simpan pembayaran')
                    ->successNotificationTitle('Pembayaran dicatat')
                    ->createAnother(false)
                    ->visible(fn (): bool => $this->getOwnerRecord()->fresh()->status_transaksi !== 'batal'
                        && $this->getOwnerRecord()->remainingAmount() > 0)
                    ->using(function (array $data): Payment {
                        try {
                            return $this->getOwnerRecord()->payments()->create($data);
                        } catch (ValidationException $exception) {
                            Notification::make()->danger()->title('Pembayaran belum disimpan')
                                ->body(collect($exception->errors())->flatten()->first())->send();
                            $path = $this->getMountedTableActionForm()->getStatePath();
                            $errors = [];
                            foreach ($exception->errors() as $field => $messages) {
                                $errors[$path.'.'.$field] = $messages;
                            }
                            throw ValidationException::withMessages($errors);
                        }
                    })
                    ->after(function (): void {
                        $this->getOwnerRecord()->refresh();
                        $this->dispatch('sale-payment-recorded', orderId: (int) $this->getOwnerRecord()->getKey());
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('Belum ada pembayaran')
            ->emptyStateDescription('Catat uang yang diterima dari pelanggan. Riwayat pembayaran yang tersimpan tidak dapat diubah atau dihapus.');
    }

    private function rupiah(float|int|string|null $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}
