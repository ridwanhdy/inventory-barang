<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanBakuDetailResource\Pages;
use App\Models\BahanBakuDetail;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BahanBakuDetailResource extends Resource
{
    protected static ?string $model = BahanBakuDetail::class;

    protected static ?string $modelLabel = 'Stok Bahan Baku';
    protected static ?string $pluralModelLabel = 'Stok Bahan Baku';

    protected static ?string $navigationLabel = 'Stok Bahan Baku';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Stok';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bahan_baku_id')
                    ->relationship('bahanBaku', 'nama_bahan')
                    ->required(),
                Select::make('satuan_id')
                    ->relationship('satuan', 'nama_satuan')
                    ->required(),
                Select::make('kategori_id')
                    ->relationship('kategori', 'nama_kategori')
                    ->required(),
                TextInput::make('stok')->numeric()->step(0.01)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['bahanBaku.kategori', 'bahanBaku.satuan']))
            ->columns([
                
                TextColumn::make('bahanBaku.nama_bahan')
                    ->label('Nama Bahan Baku'),
                TextColumn::make('stok')
                    ->label('Stok')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state)
                    ->color(function ($state) {
                        if ($state < 5) return 'danger';
                        if ($state < 10) return 'warning';
                        return 'success';
                    }),
                TextColumn::make('bahanBaku.satuan.nama_satuan')
                    ->label('Satuan'),
                TextColumn::make('bahanBaku.kategori.nama_kategori')
                    ->label('Kategori'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bahanBaku')
                    ->relationship('bahanBaku', 'nama_bahan')
                    ->label('Filter Bahan Baku')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('jenis')
                    ->relationship('bahanBaku', 'jenis')
                    ->label('Filter Jenis')
                    ->options([
                        'bahan baku' => 'Bahan Baku',
                        'bahan jadi' => 'Bahan Jadi',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBahanBakuDetails::route('/'),
            'edit' => Pages\EditBahanBakuDetail::route('/{record}/edit'),
            'view' => Pages\ViewBahanBakuDetail::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }
}
