<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductDetailResource\Pages;
use App\Filament\Resources\ProductDetailResource\RelationManagers;
use App\Models\ProductDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductDetailResource extends Resource
{
    protected static ?string $model = ProductDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Stok Produk';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationGroup = 'Stok';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['product.kategori', 'product.satuan']))
            ->columns([
                Tables\Columns\TextColumn::make('product.nama_product')
                    ->label('Nama Produk'),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state)
                    ->color(function ($state) {
                        if ($state < 5) return 'danger';
                        if ($state < 10) return 'warning';
                        return 'success';
                    }),
                Tables\Columns\TextColumn::make('product.satuan.nama_satuan')
                    ->label('Satuan'),
                Tables\Columns\TextColumn::make('product.kategori.nama_kategori')
                    ->label('Kategori'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'nama_product')
                    ->label('Filter Produk')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('kategori')
                    ->relationship('product.kategori', 'nama_kategori')
                    ->label('Filter Kategori'),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListProductDetails::route('/'),
            'create' => Pages\CreateProductDetail::route('/create'),
            'edit' => Pages\EditProductDetail::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }
}
