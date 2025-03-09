<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangResource\Pages;
use App\Filament\Resources\BarangResource\RelationManagers;
use App\Models\Barang;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;

class BarangResource extends Resource
{
    protected static ?string $model = Barang::class;

    protected static ?string $navigationGroup = 'Master Data Barang';

    protected static ?string $navigationLabel = 'Barang';

    protected static ?int $navigationSort = 1; 


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama_barang'),
                Select::make('jenis_id')
                ->label('Jenis Barang')
                ->relationship('jenis', 'nama_jenis')
                ->searchable()
                ->preload()
                ->required(),
                Select::make('satuan_id')
                ->label('Satuan')
                ->relationship('satuan', 'nama_satuan')
                ->searchable()
                ->preload()
                ->required(),
                Forms\Components\Select::make('kategori')
    ->label('Kategori')
    ->options([
        'Bahan Baku' => 'Bahan Baku',
        'Barang Jadi' => 'Barang Jadi',
    ])
    ->required(),
                Forms\Components\TextInput::make('stok')->numeric()->disabled(),
                Forms\Components\TextInput::make('stok_minimum')->numeric()->minValue(0)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_barang'),
                Tables\Columns\BadgeColumn::make('stok')->label('Stok Tersedia')->sortable()->sortable()
                ->colors([
                    'danger' => fn ($record) => $record->stok < $record->stok_minimum, // Merah
                    'warning' => fn ($record) => $record->stok == $record->stok_minimum, // Kuning
                    'success' => fn ($record) => $record->stok > $record->stok_minimum, // Hijau
                ]),
                Tables\Columns\TextColumn::make('satuan.nama_satuan')->label('Satuan'),
                Tables\Columns\TextColumn::make('kategori')->label('Kategori'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBarangs::route('/'),
            'create' => Pages\CreateBarang::route('/create'),
            'edit' => Pages\EditBarang::route('/{record}/edit'),
        ];
    }
}
