<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Support\MasterDataUsage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $activeNavigationIcon = 'heroicon-s-shopping-bag';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make('Identitas produk')
                    ->description('Atur nama, kategori, dan satuan produk untuk pencatatan produksi dan penjualan.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_product')
                            ->label('Nama produk')
                            ->placeholder('Contoh: Kaos Polos')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->columnSpanFull(),
                        Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship('kategori', 'nama_kategori')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kategori produk')
                            ->required(),
                        Select::make('satuan_id')
                            ->label('Satuan')
                            ->relationship('satuan', 'nama_satuan')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih satuan produk')
                            ->helperText('Satuan yang digunakan saat mencatat jumlah produk.')
                            ->required(),
                    ]),
                Section::make('Varian produk')
                    ->description('Setiap kombinasi ukuran, warna, dan bahan dicatat sebagai varian tersendiri. Nama produk dapat sama untuk varian berbeda.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('ukuran')
                            ->label('Ukuran')
                            ->placeholder('Contoh: M atau XL')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? '')),
                        TextInput::make('warna')
                            ->label('Warna')
                            ->placeholder('Contoh: Hitam')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? '')),
                        TextInput::make('bahan')
                            ->label('Bahan')
                            ->placeholder('Contoh: Cotton Combed 30s')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? '')),
                    ]),
                Section::make('Harga dan foto')
                    ->description('Tentukan harga jual per satuan dan tambahkan foto untuk memudahkan pengenalan produk.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('harga_jual')
                            ->label('Harga jual')
                            ->prefix('Rp')
                            ->placeholder('Contoh: 75000')
                            ->helperText('Harga per satuan dalam rupiah, tanpa titik atau koma.')
                            ->integer()
                            ->minValue(0)
                            ->required(),
                        FileUpload::make('foto')
                            ->label('Foto produk')
                            ->disk('public')
                            ->directory('produk')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(5120)
                            ->imagePreviewHeight('220')
                            ->openable()
                            ->downloadable()
                            ->placeholder('Seret foto ke sini atau <span class="filepond--label-action">pilih foto</span>')
                            ->helperText('Foto opsional. Gunakan JPG, PNG, atau WebP, maksimal 5 MB.')
                            ->validationMessages([
                                'mimetypes' => 'Gunakan foto JPG, PNG, atau WebP.',
                                'max' => 'Ukuran foto maksimal 5 MB.',
                            ]),
                    ]),
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin' && ! MasterDataUsage::isUsedForDisplay($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => MasterDataUsage::withUsage($query))
            ->recordTitleAttribute('nama_product')
            ->defaultSort('nama_product')
            ->searchPlaceholder('Cari nama, varian, kategori, atau satuan...')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Tidak ada produk yang ditampilkan')
            ->emptyStateDescription('Tambahkan produk baru atau sesuaikan pencarian dan filter yang digunakan.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->columns([
                ImageColumn::make('foto')
                    ->label('Foto Produk')
                    ->getStateUsing(fn (Product $record) => filled($record->foto) && Storage::disk('public')->exists($record->foto) ? $record->foto : null)
                    ->disk('public')
                    ->visibility('public')
                    ->defaultImageUrl(asset('images/product-placeholder.svg'))
                    ->square()
                    ->width(50)
                    ->height(50),
                TextColumn::make('nama_product')
                    ->label('Nama produk')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('ukuran')
                    ->label('Ukuran')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warna')
                    ->label('Warna')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bahan')
                    ->label('Bahan')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('harga_jual')
                    ->label('Harga jual')
                    ->sortable()
                    ->alignEnd()
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('satuan_id')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('ukuran')
                    ->label('Ukuran')
                    ->options(fn (): array => Product::query()->whereNotNull('ukuran')->where('ukuran', '!=', '')->distinct()->orderBy('ukuran')->pluck('ukuran', 'ukuran')->all())
                    ->searchable(),
                SelectFilter::make('warna')
                    ->label('Warna')
                    ->options(fn (): array => Product::query()->whereNotNull('warna')->where('warna', '!=', '')->distinct()->orderBy('warna')->pluck('warna', 'warna')->all())
                    ->searchable(),
                SelectFilter::make('bahan')
                    ->label('Bahan')
                    ->options(fn (): array => Product::query()->whereNotNull('bahan')->where('bahan', '!=', '')->distinct()->orderBy('bahan')->pluck('bahan', 'bahan')->all())
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus produk')
                    ->modalDescription('Produk yang belum digunakan akan dihapus. Tindakan ini tidak dapat dibatalkan.')
                    ->modalSubmitActionLabel('Hapus produk')
                    ->modalCancelActionLabel('Batal'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ProduksiRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
