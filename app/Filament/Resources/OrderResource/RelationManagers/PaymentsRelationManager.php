<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Pembayaran';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('jumlah_bayar')
                    ->required()
                    ->numeric()
                    ->label('Jumlah Bayar')
                    ->prefix('Rp')
                    ->live()
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        
                        // Get total of previous payments excluding current payment
                        $currentPaymentId = $get('id');
                        $previousPayments = $order->payments()
                            ->when($currentPaymentId, fn($query) => $query->where('id', '!=', $currentPaymentId))
                            ->sum('jumlah_bayar');
                            
                        if ($totalHarga && $state) {
                            $sisaBayar = max(0, $totalHarga - ($previousPayments + $state));
                            $set('sisa_bayar', $sisaBayar);
                            
                            // Calculate kembalian
                            $kembalian = max(0, $state - ($totalHarga - $previousPayments));
                            $set('kembalian', $kembalian);
                        }
                    }),
                
                Forms\Components\TextInput::make('sisa_bayar')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->label('Sisa Bayar')
                    ->prefix('Rp')
                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        
                        // Get total of previous payments excluding current payment
                        $currentPaymentId = $get('id');
                        $previousPayments = $order->payments()
                            ->when($currentPaymentId, fn($query) => $query->where('id', '!=', $currentPaymentId))
                            ->sum('jumlah_bayar');
                            
                        $jumlahBayar = $get('jumlah_bayar');
                        if ($totalHarga && $jumlahBayar) {
                            $sisaBayar = max(0, $totalHarga - ($previousPayments + $jumlahBayar));
                            $set('sisa_bayar', $sisaBayar);
                            
                            // Calculate kembalian
                            $kembalian = max(0, $jumlahBayar - ($totalHarga - $previousPayments));
                            $set('kembalian', $kembalian);
                        }
                    }),

                Forms\Components\TextInput::make('kembalian')
                    ->required()
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->label('Kembalian')
                    ->prefix('Rp'),

                Forms\Components\Select::make('metode_pembayaran')
                    ->options([
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                    ])
                    ->required()
                    ->default('cash')
                    ->label('Metode Pembayaran'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('jumlah_bayar')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah Bayar'),
                Tables\Columns\TextColumn::make('sisa_bayar')
                    ->money('IDR')
                    ->sortable()
                    ->label('Sisa Bayar'),
                Tables\Columns\TextColumn::make('kembalian')
                    ->money('IDR')
                    ->sortable()
                    ->label('Kembalian'),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'success',
                        'bank' => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Cash',
                        'bank' => 'Bank',
                    })
                    ->sortable()
                    ->label('Metode Pembayaran'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Bayar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pembayaran')
                    ->visible(function () {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        $totalBayar = $order->payments()->sum('jumlah_bayar');
                        return $totalBayar < $totalHarga;
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        
                        // Get total of previous payments
                        $previousPayments = $order->payments()->sum('jumlah_bayar');
                        
                        $data['sisa_bayar'] = max(0, $totalHarga - ($previousPayments + $data['jumlah_bayar']));
                        $data['kembalian'] = max(0, $data['jumlah_bayar'] - ($totalHarga - $previousPayments));
                        return $data;
                    })
                    ->after(function ($record) {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        $totalBayar = $order->payments()->sum('jumlah_bayar');
                        
                        // Update status pembayaran
                        if ($totalBayar >= $totalHarga) {
                            $order->update(['status_pembayaran' => 'lunas']);
                        } else if ($totalBayar > 0) {
                            $order->update(['status_pembayaran' => 'cicilan']);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        
                        // Get total of previous payments excluding current payment
                        $previousPayments = $order->payments()
                            ->where('id', '!=', $record->id)
                            ->sum('jumlah_bayar');
                        
                        $data['sisa_bayar'] = max(0, $totalHarga - ($previousPayments + $data['jumlah_bayar']));
                        $data['kembalian'] = max(0, $data['jumlah_bayar'] - ($totalHarga - $previousPayments));
                        return $data;
                    })
                    ->after(function ($record) {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        $totalBayar = $order->payments()->sum('jumlah_bayar');
                        
                        // Update status pembayaran
                        if ($totalBayar >= $totalHarga) {
                            $order->update(['status_pembayaran' => 'lunas']);
                        } else if ($totalBayar > 0) {
                            $order->update(['status_pembayaran' => 'cicilan']);
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->after(function ($record) {
                        $order = $this->getOwnerRecord();
                        $totalHarga = $order->orderDetails->sum(function ($detail) {
                            return $detail->quantity * $detail->harga;
                        });
                        $totalBayar = $order->payments()->sum('jumlah_bayar');
                        
                        // Update status pembayaran
                        if ($totalBayar >= $totalHarga) {
                            $order->update(['status_pembayaran' => 'lunas']);
                        } else if ($totalBayar > 0) {
                            $order->update(['status_pembayaran' => 'cicilan']);
                        } else {
                            $order->update(['status_pembayaran' => 'belum_bayar']);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 