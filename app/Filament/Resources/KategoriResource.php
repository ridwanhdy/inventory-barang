<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KategoriResource\Pages;
use App\Models\Kategori;
use App\Support\MasterDataUsage;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KategoriResource extends Resource
{
    protected static ?string $model = Kategori::class;

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $activeNavigationIcon = 'heroicon-s-tag';

    protected static ?string $recordTitleAttribute = 'nama_kategori';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi kategori')
                    ->description('Kelompokkan bahan baku dan produk agar mudah dicari dan dilaporkan. Kategori yang sedang digunakan tidak dapat dihapus.')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        TextInput::make('nama_kategori')
                            ->label('Nama kategori')
                            ->placeholder('Contoh: Kain, Benang, atau Kaos')
                            ->helperText('Gunakan nama yang jelas dan berbeda dari kategori lainnya.')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Nama kategori wajib diisi.',
                                'unique' => 'Nama kategori sudah digunakan. Pilih nama yang berbeda.',
                                'max' => 'Nama kategori maksimal 255 karakter.',
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['bahanBakus', 'product']);
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
            ->defaultSort('nama_kategori')
            ->striped()
            ->paginated([10, 25, 50])
            ->searchPlaceholder('Cari nama kategori...')
            ->emptyStateIcon('heroicon-o-folder')
            ->emptyStateHeading('Kategori tidak ditemukan')
            ->emptyStateDescription('Tambahkan kategori untuk mengelompokkan bahan baku dan produk, atau sesuaikan pencarian dan filter.')
            ->columns([
                TextColumn::make('nama_kategori')
                    ->label('Nama kategori')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->wrap(),
                TextColumn::make('bahan_bakus_count')
                    ->label('Bahan baku')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('product_count')
                    ->label('Produk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('status_penggunaan')
                    ->label('Penggunaan')
                    ->state(fn (Kategori $record): string => ($record->bahan_bakus_count + $record->product_count) > 0 ? 'Digunakan' : 'Belum digunakan')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Digunakan' ? 'success' : 'gray')
                    ->tooltip('Kategori yang digunakan oleh bahan baku atau produk tidak dapat dihapus.'),
                TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('digunakan')
                    ->label('Penggunaan')
                    ->placeholder('Semua kategori')
                    ->trueLabel('Digunakan')
                    ->falseLabel('Belum digunakan')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where(fn (Builder $query): Builder => $query->has('bahanBakus')->orHas('product')),
                        false: fn (Builder $query): Builder => $query->doesntHave('bahanBakus')->doesntHave('product'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus kategori')
                    ->modalDescription('Kategori yang belum digunakan akan dihapus. Kategori yang digunakan bahan baku atau produk dilindungi dari penghapusan.')
                    ->modalSubmitActionLabel('Ya, hapus kategori'),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListKategoris::route('/'),
            'create' => Pages\CreateKategori::route('/create'),
            'edit' => Pages\EditKategori::route('/{record}/edit'),
        ];
    }
}
