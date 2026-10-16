<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanBakuResource\Pages;
use App\Models\BahanBaku;
use App\Support\MasterDataUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BahanBakuResource extends Resource
{
    protected static ?string $model = BahanBaku::class;

    protected static ?string $modelLabel = 'Bahan Baku';

    protected static ?string $pluralModelLabel = 'Bahan Baku';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Bahan Baku';

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $activeNavigationIcon = 'heroicon-s-swatch';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas bahan')
                    ->description('Daftarkan bahan untuk penerimaan dan produksi. Stok awal otomatis nol; bahan masuk dicatat melalui Penerimaan Bahan Baku.')
                    ->schema([
                        Forms\Components\TextInput::make('nama_bahan')
                            ->label('Nama bahan')
                            ->placeholder('Contoh: Kain Cotton Combed 30s Hitam')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->columnSpanFull(),
                        Forms\Components\Select::make('kategori_id')
                            ->label('Kategori')
                            ->relationship('kategori', 'nama_kategori', fn (Builder $query) => $query->orderBy('nama_kategori'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih kategori bahan')
                            ->helperText('Contoh kategori: Kain, Benang, Label, atau Kemasan.')
                            ->required(),
                        Forms\Components\Select::make('satuan_id')
                            ->label('Satuan')
                            ->relationship('satuan', 'nama_satuan', fn (Builder $query) => $query->orderBy('nama_satuan'))
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih satuan pencatatan')
                            ->helperText('Gunakan satuan yang sama saat penerimaan dan pemakaian bahan.')
                            ->required(),
                    ])
                    ->columns(2),
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
            ->modifyQueryUsing(fn (Builder $query): Builder => MasterDataUsage::withUsage($query)->with(['satuan', 'kategori']))
            ->description('Identitas bahan untuk transaksi dan produksi. Data yang sudah dipakai atau memiliki stok tidak dapat dihapus.')
            ->searchPlaceholder('Cari nama, kategori, atau satuan')
            ->defaultSort('nama_bahan')
            ->striped()
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                TextColumn::make('nama_bahan')
                    ->label('Nama bahan')
                    ->weight(FontWeight::Medium)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('satuan.nama_satuan')
                    ->label('Satuan')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->relationship('kategori', 'nama_kategori')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua kategori'),
                Tables\Filters\SelectFilter::make('satuan_id')
                    ->label('Satuan')
                    ->relationship('satuan', 'nama_satuan')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua satuan'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Bahan baku tidak ditemukan')
            ->emptyStateDescription('Tambahkan bahan baku pertama atau sesuaikan pencarian dan filter.');
    }

    public static function getRelations(): array
    {
        return [];
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
