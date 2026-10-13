<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Support\MasterDataUsage;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
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

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_product')->required(),
                Select::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->required(),
                Select::make('satuan_id')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->required(),
                TextInput::make('ukuran')->required(),
                TextInput::make('warna')->required(),
                TextInput::make('bahan')->required(),
                TextInput::make('harga_jual')->numeric()->required(),
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
            ]);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'admin' && ! MasterDataUsage::isUsed($record);
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
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
                TextColumn::make('nama_product')->label('Nama produk')->searchable(),
                TextColumn::make('kategori.nama_kategori'),
                TextColumn::make('satuan.nama_satuan'),
                TextColumn::make('harga_jual')
                    ->label('Harga jual')
                    ->formatStateUsing(fn ($state): string => 'Rp' . number_format((float) $state, 0, ',', '.')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus'),
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
