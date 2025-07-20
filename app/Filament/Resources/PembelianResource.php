<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\Pembelian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $navigationLabel = 'Pembelian';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin' || auth()->user()->role === 'kasir';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Pembelian')
                        ->schema([
                            Forms\Components\Select::make('pemasok_id')
                                ->relationship('pemasok', 'nama_pemasok')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->label('Pemasok'),
                            Forms\Components\DatePicker::make('tanggal_pembelian')
                                ->required()
                                ->label('Tanggal Pembelian')
                                ->default(now()),
                            Forms\Components\Textarea::make('catatan')
                                ->label('Catatan')
                                ->rows(3)
                                ->placeholder('Catatan tambahan untuk pembelian ini...'),
                        ])
                        ->columns(2)
                        ->columnSpan('full'),
                    Forms\Components\Wizard\Step::make('Tambah Bahan Baku')
                        ->schema([
                            Forms\Components\Repeater::make('pembelianDetails')
                                ->relationship()
                                ->schema([
                                    Forms\Components\Select::make('bahan_baku_id')
                                        ->relationship('bahanBaku', 'nama_bahan')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->label('Bahan Baku')
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if ($state) {
                                                $bahanBaku = \App\Models\BahanBaku::find($state);
                                                if ($bahanBaku) {
                                                    $set('satuan', $bahanBaku->satuan->nama_satuan);
                                                } else {
                                                    $set('satuan', '');
                                                }
                                            } else {
                                                $set('satuan', '');
                                            }
                                        }),
                                    Forms\Components\TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->label('Quantity')
                                        ->live(),
                                    Forms\Components\TextInput::make('satuan')
                                        ->label('Satuan')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->live()
                                        ->visible(fn (callable $get) => $get('bahan_baku_id') !== null),
                                ])
                                ->columns(3)
                                ->defaultItems(1)
                                ->addActionLabel('Tambah Bahan Baku')
                                ->label('Detail Pembelian')
                                ->required()
                                ->minItems(1)
                                ->live(),
                        ])
                        ->columnSpan('full'),
                ])
                ->columnSpan('full')
                ->skippable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('pemasok.nama_pemasok')
                    ->searchable()
                    ->sortable()
                    ->label('Pemasok'),
                
                Tables\Columns\TextColumn::make('pembelianDetails.bahanBaku.nama_bahan')
                    ->listWithLineBreaks()
                    ->label('Bahan Baku'),
                
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->getStateUsing(function (Pembelian $record) {
                        return $record->pembelianDetails->sum('quantity');
                    }),
                
                Tables\Columns\TextColumn::make('tanggal_pembelian')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Pembelian'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPembelians::route('/'),
            'create' => Pages\CreatePembelian::route('/create'),
            'view' => Pages\ViewPembelian::route('/{record}'),
            'edit' => Pages\EditPembelian::route('/{record}/edit'),
        ];
    }
}
