<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduksiDetailRelationManagerResource\RelationManagers\ProduksiDetailRelationManager;
use App\Filament\Resources\ProduksiResource\Pages;
use App\Filament\Resources\ProduksiResource\RelationManagers;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProduksiResource extends Resource
{
    protected static ?string $model = Produksi::class;

    protected static ?string $navigationGroup = 'Manajemen Produksi';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Produksi';
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                ->relationship('product', 'nama_product')
                ->required(),
            Forms\Components\TextInput::make('jumlah_produksi')->numeric()->required(),
            Forms\Components\DatePicker::make('produksi_mulai')->required(),
            Forms\Components\DatePicker::make('produksi_selesai')->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'Proses' => 'Proses',
                    'Batal' => 'Batal',
                    'Selesai' => 'Selesai',
                ])
                ->default('Proses')
                ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.nama_product')->label('Produk'),
                Tables\Columns\TextColumn::make('jumlah_produksi'),
                Tables\Columns\TextColumn::make('produksi_mulai'),
                Tables\Columns\TextColumn::make('produksi_selesai')->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'Proses' => 'Proses',
                        'Batal' => 'Batal',
                        'Selesai' => 'Selesai',
                    ])
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'nama_product')
                    ->label('Produk')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Proses' => 'Proses',
                        'Batal' => 'Batal',
                        'Selesai' => 'Selesai',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\SelectAction::make('status')
                    ->label('Status')
                    ->options([
                        'Proses' => 'Proses',
                        'Batal' => 'Batal',
                        'Selesai' => 'Selesai',
                    ])
                    ->action(function ($record, $state) {
                        $record->update(['status' => $state]);
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
            ProduksiDetailRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduksis::route('/'),
            'create' => Pages\CreateProduksi::route('/create'),
            'edit' => Pages\EditProduksi::route('/{record}/edit'),
        ];
    }
}
