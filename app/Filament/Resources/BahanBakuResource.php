<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanBakuResource\Pages;
use App\Filament\Resources\BahanBakuResource\RelationManagers;
use App\Models\BahanBaku;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;

class BahanBakuResource extends Resource
{
    protected static ?string $model = BahanBaku::class;

    protected static ?string $navigationGroup = 'Manajemen Bahan';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Bahan Baku';


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_bahan')
                    ->label('Nama Bahan')
                    ->required(),

                // Select untuk Satuan
                Select::make('satuan_id')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->required(),

                // Select untuk Kategori
                Select::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->required(),

                // Select untuk Jenis
                Select::make('jenis_id')
                    ->label('Jenis')
                    ->relationship('jenis', 'nama_jenis')
                    ->default(1) // Bisa diganti sesuai dengan ID jenis default yang diinginkan
                    ->disabled()
                    ->required(),

                // Input untuk stok
                Forms\Components\TextInput::make('stok')
                    ->label('Stok')
                    ->required()
                    ->numeric(),

                // Input untuk stok minimal
                Forms\Components\TextInput::make('stok_minimal')
                    ->label('Stok Minimal')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_bahan')
                    ->label('Nama Bahan'),
                
                // Menampilkan nama satuan dari relasi
                TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan'),

                // Menampilkan nama kategori dari relasi
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori'),

                // Menampilkan nama jenis dari relasi
                TextColumn::make('jenis.nama_jenis')
                    ->label('Jenis'),

                TextColumn::make('stok')
                    ->label('Stok'),

                TextColumn::make('stok_minimal')
                    ->label('Stok Minimal'),
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
            'index' => Pages\ListBahanBakus::route('/'),
            'create' => Pages\CreateBahanBaku::route('/create'),
            'edit' => Pages\EditBahanBaku::route('/{record}/edit'),
        ];
    }
}
