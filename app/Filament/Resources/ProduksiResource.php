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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                ->relationship('product', 'nama_product')
                ->required(),
            Forms\Components\TextInput::make('jumlah_produksi')->numeric()->required(),
            Forms\Components\DatePicker::make('produksi_mulai')->required(),
            Forms\Components\DatePicker::make('produksi_selesai'),
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
            Tables\Columns\BadgeColumn::make('status'),
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
