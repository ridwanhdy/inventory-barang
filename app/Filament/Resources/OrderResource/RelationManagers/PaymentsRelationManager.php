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
                        if ($totalHarga && $state) {
                            $sisaBayar = max(0, $totalHarga - $state);
                            $set('sisa_bayar', $sisaBayar);
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
                        $jumlahBayar = $get('jumlah_bayar');
                        if ($totalHarga && $jumlahBayar) {
                            $sisaBayar = max(0, $totalHarga - $jumlahBayar);
                            $set('sisa_bayar', $sisaBayar);
                        }
                    }),
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
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Pembayaran'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
} 