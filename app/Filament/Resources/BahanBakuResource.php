<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanBakuResource\Pages;
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
use Filament\Infolists\Infolist;

class BahanBakuResource extends Resource
{
    protected static ?string $model = BahanBaku::class;

    protected static ?string $navigationGroup = 'Manajemen Bahan';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Bahan Baku';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected ?string $heading = 'Bahan Baku';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_bahan')
                    ->label('Nama Bahan')
                    ->required(),

                Select::make('satuan_id')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->required(),

                Select::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->required(),

                Select::make('jenis_id')
                    ->label('Jenis')
                    ->relationship('jenis', 'nama_jenis')
                    ->required(),

                TextInput::make('stok')
                    ->label('Stok')
                    ->required()
                    ->numeric()
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(fn ($record) => $record === null),

                TextInput::make('stok_minimal')
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
                    ->label('Nama Bahan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->sortable(),
                
                TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan'),

                

                TextColumn::make('stok_minimal')
                    ->label('Stok Minimal')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('increaseStock')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                    ])
                    ->action(function (BahanBaku $record, array $data): void {
                        $record->update([
                            'stok' => $record->stok + $data['amount']
                        ]);
                    }),
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
            'view' => Pages\ViewBahanBaku::route('/{record}'),
            'edit' => Pages\EditBahanBaku::route('/{record}/edit'),
        ];
    }
}
