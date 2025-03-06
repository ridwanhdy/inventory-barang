<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarangMasukResource\Pages;
use App\Filament\Resources\BarangMasukResource\RelationManagers;
use App\Models\BarangMasuk;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Barang;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;

class BarangMasukResource extends Resource
{
    protected static ?string $model = BarangMasuk::class;

    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?int $navigationSort = 4; // Sesuaikan angka untuk mengatur urutan


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('barang_id')
                    ->label('Barang')
                    ->options(Barang::pluck('nama_barang', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('jumlah_masuk')
                    ->label('Jumlah Masuk')
                    ->numeric()
                    ->required(),

                DatePicker::make('tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->default(now()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('barang.nama_barang')->label('Barang'),
                TextColumn::make('jumlah_masuk')->label('Jumlah Masuk'),
                TextColumn::make('tanggal_masuk')->label('Tanggal Masuk')->date(),
            ])
            ->filters([
                Filter::make('tanggal_masuk')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(fn ($query, array $data) => 
                        $query
                            ->when($data['from'], fn ($query) => $query->whereDate('tanggal_masuk', '>=', $data['from']))
                            ->when($data['until'], fn ($query) => $query->whereDate('tanggal_masuk', '<=', $data['until']))
                    ),
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
            'index' => Pages\ListBarangMasuks::route('/'),
            'create' => Pages\CreateBarangMasuk::route('/create'),
            'edit' => Pages\EditBarangMasuk::route('/{record}/edit'),
        ];
    }
}
