<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductDetailResource\Pages;
use App\Filament\Resources\ProductDetailResource\RelationManagers;
use App\Models\ProductDetail;
use App\Support\StockPresentation;
use Filament\Support\Enums\FontWeight;
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

    protected static ?string $modelLabel = 'Stok Produk Jadi';
    protected static ?string $pluralModelLabel = 'Stok Produk Jadi';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $activeNavigationIcon = 'heroicon-s-archive-box';

    protected static ?string $navigationLabel = 'Stok Produk Jadi';
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.kategori', 'product.satuan']))
            ->searchPlaceholder('Cari nama atau varian')
            ->defaultSort('product.nama_product')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('product.nama_product')
                    ->label('Produk & varian')
                    ->description(fn (ProductDetail $record) => collect([
                        filled($record->product?->ukuran) ? 'Ukuran ' . $record->product->ukuran : null,
                        $record->product?->warna,
                    ])->filter()->join(' · '))
                    ->weight(FontWeight::Medium)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'product',
                        fn (Builder $query) => $query->where('nama_product', 'like', "%{$search}%")
                            ->orWhere('ukuran', 'like', "%{$search}%")
                            ->orWhere('warna', 'like', "%{$search}%")
                            ->orWhereHas('kategori', fn (Builder $query) => $query->where('nama_kategori', 'like', "%{$search}%")),
                    ))
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Jumlah stok')
                    ->formatStateUsing(fn ($state, ProductDetail $record) => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan))
                    ->weight(FontWeight::SemiBold)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_stok')
                    ->label('Ketersediaan')
                    ->getStateUsing(fn (ProductDetail $record) => StockPresentation::status($record->stok, StockPresentation::minimum($record)))
                    ->badge()
                    ->color(fn (ProductDetail $record) => StockPresentation::color($record->stok, StockPresentation::minimum($record)))
                    ->tooltip(fn (ProductDetail $record) => $record->stok < 0 ? 'Stok negatif. Periksa pencatatan produksi dan penjualan.' : null),
                Tables\Columns\TextColumn::make('product.ukuran')
                    ->label('Ukuran')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product.warna')
                    ->label('Warna')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product.kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product')
                    ->relationship('product', 'nama_product')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->nama_product . ' · ' . $record->warna . ' · ' . $record->ukuran)
                    ->label('Produk')
                    ->placeholder('Semua produk')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('kategori')
                    ->relationship('product.kategori', 'nama_kategori')
                    ->label('Kategori')
                    ->placeholder('Semua kategori')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status_stok')
                    ->label('Ketersediaan')
                    ->placeholder('Semua status')
                    ->options(fn () => StockPresentation::statusOptions('product_details'))
                    ->query(fn (Builder $query, array $data) => StockPresentation::filter($query, $data['value'] ?? null)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail stok'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada produk yang ditampilkan')
            ->emptyStateDescription('Ubah pencarian atau filter. Untuk produk baru, tambahkan melalui Master Data Produk.');
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
            'view' => Pages\ViewProductDetail::route('/{record}'),
            'edit' => Pages\EditProductDetail::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
