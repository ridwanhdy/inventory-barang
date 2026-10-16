<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PembelianResource\Pages;
use App\Models\BahanBaku;
use App\Models\Pembelian;
use App\Support\StockPresentation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static ?string $modelLabel = 'Penerimaan Bahan Baku';
    protected static ?string $pluralModelLabel = 'Penerimaan Bahan Baku';
    protected static ?string $navigationLabel = 'Penerimaan Bahan Baku';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 5;
    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $activeNavigationIcon = 'heroicon-s-inbox-arrow-down';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi penerimaan')
                ->description('Catat bahan baku yang sudah diterima dari pemasok. Jumlah yang disimpan akan menambah stok bahan baku.')
                ->schema([
                    Forms\Components\Select::make('pemasok_id')
                        ->relationship('pemasok', 'nama_pemasok')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->label('Pemasok'),
                    Forms\Components\DatePicker::make('tanggal_pembelian')
                        ->required()
                        ->label('Tanggal diterima')
                        ->default(today())
                        ->maxDate(today()),
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan')
                        ->rows(2)
                        ->placeholder('Nomor nota pemasok atau keterangan penerimaan')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Bahan baku yang diterima')
                ->schema([
                    Forms\Components\Repeater::make('pembelianDetails')
                        ->relationship()
                        ->schema([
                            Forms\Components\Select::make('bahan_baku_id')
                                ->relationship('bahanBaku', 'nama_bahan', fn (Builder $query) => $query->where('jenis', 'bahan baku')->with('satuan'))
                                ->getOptionLabelFromRecordUsing(fn (BahanBaku $record): string => $record->nama_bahan . ' (' . ($record->satuan?->nama_satuan ?? 'satuan belum diatur') . ')')
                                ->searchable()
                                ->preload()
                                ->live()
                                ->required()
                                ->label('Bahan baku')
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->afterStateUpdated(function ($state, Forms\Set $set): void {
                                    $set('satuan', BahanBaku::find($state)?->satuan?->nama_satuan);
                                }),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->maxValue(999999.99)
                                ->step(0.01)
                                ->rules(['decimal:0,2'])
                                ->label('Jumlah diterima'),
                            Forms\Components\TextInput::make('satuan')
                                ->label('Satuan')
                                ->disabled()
                                ->dehydrated(false)
                                ->afterStateHydrated(function (Forms\Get $get, Forms\Set $set): void {
                                    $set('satuan', BahanBaku::find($get('bahan_baku_id'))?->satuan?->nama_satuan);
                                }),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah bahan baku')
                        ->label('Rincian penerimaan')
                        ->required()
                        ->minItems(1)
                        ->reorderable(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Riwayat bahan baku yang diterima dari pemasok. Koreksi jumlah penerimaan akan menyesuaikan stok bahan baku.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['pemasok', 'pembelianDetails.bahanBaku.satuan']))
            ->defaultSort('tanggal_pembelian', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pembelian')
                    ->date('d M Y')
                    ->sortable()
                    ->label('Tanggal diterima'),
                Tables\Columns\TextColumn::make('pemasok.nama_pemasok')
                    ->searchable()
                    ->sortable()
                    ->label('Pemasok'),
                Tables\Columns\TextColumn::make('bahan_diterima')
                    ->label('Bahan baku dan jumlah diterima')
                    ->getStateUsing(fn (Pembelian $record): array => $record->pembelianDetails->map(
                        fn ($detail): string => ($detail->bahanBaku?->nama_bahan ?? 'Bahan baku tidak tersedia') . ' — ' . StockPresentation::quantity($detail->quantity, $detail->bahanBaku?->satuan?->nama_satuan),
                    )->all())
                    ->listWithLineBreaks()
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('pembelianDetails.bahanBaku', fn (Builder $query) => $query->where('nama_bahan', 'like', '%' . $search . '%'))),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(60)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('pemasok')
                    ->relationship('pemasok', 'nama_pemasok')
                    ->label('Pemasok')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat penerimaan'),
                Tables\Actions\EditAction::make()->label('Koreksi penerimaan'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
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
