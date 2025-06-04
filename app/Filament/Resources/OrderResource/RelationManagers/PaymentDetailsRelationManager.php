<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Payment;

class PaymentDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentDetails';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Pelunasan';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nominal_bayar')
                    ->required()
                    ->numeric()
                    ->label('Nominal Bayar')
                    ->prefix('Rp')
                    ->maxValue(function () {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        return $payment ? $payment->sisa_bayar : 0;
                    })
                    ->helperText(function () {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        return $payment ? 'Sisa Bayar: Rp ' . number_format($payment->sisa_bayar, 0, ',', '.') : 'Tidak ada sisa bayar';
                    }),
                Forms\Components\DatePicker::make('tanggal_bayar')
                    ->required()
                    ->label('Tanggal Bayar')
                    ->default(now())
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('nominal_bayar')
                    ->money('IDR')
                    ->sortable()
                    ->label('Nominal Bayar'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal Bayar'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pelunasan')
                    ->mutateFormDataUsing(function (array $data): array {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        $data['payment_id'] = $payment->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        if ($payment) {
                            $payment->update([
                                'sisa_bayar' => $payment->sisa_bayar - $record->nominal_bayar
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        if ($payment) {
                            // Hitung selisih antara nominal baru dan lama
                            $selisih = $record->nominal_bayar - $record->getOriginal('nominal_bayar');
                            $payment->update([
                                'sisa_bayar' => $payment->sisa_bayar - $selisih
                            ]);
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->before(function ($record) {
                        $order = $this->getOwnerRecord();
                        $payment = Payment::where('order_id', $order->id)->first();
                        if ($payment) {
                            // Kembalikan nominal yang dihapus ke sisa bayar
                            $payment->update([
                                'sisa_bayar' => $payment->sisa_bayar + $record->nominal_bayar
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            $order = $this->getOwnerRecord();
                            $payment = Payment::where('order_id', $order->id)->first();
                            if ($payment) {
                                // Kembalikan total nominal yang dihapus ke sisa bayar
                                $totalDihapus = $records->sum('nominal_bayar');
                                $payment->update([
                                    'sisa_bayar' => $payment->sisa_bayar + $totalDihapus
                                ]);
                            }
                        }),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('payment', function ($query) {
                $query->where('order_id', $this->getOwnerRecord()->id);
            }));
    }
} 