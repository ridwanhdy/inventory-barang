<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SatuanResource\Pages;
use App\Models\Satuan;
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

class SatuanResource extends Resource
{
    protected static ?string $model = Satuan::class;

    protected static ?string $modelLabel = 'Satuan';

    protected static ?string $pluralModelLabel = 'Satuan';

    protected static ?string $navigationLabel = 'Satuan';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $activeNavigationIcon = 'heroicon-s-scale';

    protected static ?string $recordTitleAttribute = 'nama_satuan';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi satuan')
                    ->description('Samakan satuan pengukuran bahan baku dan produk agar pencatatan stok konsisten. Satuan yang sedang digunakan tidak dapat dihapus.')
                    ->icon('heroicon-o-scale')
                    ->schema([
                        TextInput::make('nama_satuan')
                            ->label('Nama satuan')
                            ->placeholder('Contoh: meter, kilogram, atau pcs')
                            ->helperText('Gunakan satu nama untuk setiap satuan agar tidak ada pencatatan ganda.')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'required' => 'Nama satuan wajib diisi.',
                                'unique' => 'Nama satuan sudah digunakan. Pilih nama yang berbeda.',
                                'max' => 'Nama satuan maksimal 255 karakter.',
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
            ->defaultSort('nama_satuan')
            ->striped()
            ->paginated([10, 25, 50])
            ->searchPlaceholder('Cari nama satuan...')
            ->emptyStateIcon('heroicon-o-scale')
            ->emptyStateHeading('Satuan tidak ditemukan')
            ->emptyStateDescription('Tambahkan satuan pengukuran untuk bahan baku dan produk, atau sesuaikan pencarian dan filter.')
            ->columns([
                TextColumn::make('nama_satuan')
                    ->label('Nama satuan')
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
                    ->state(fn (Satuan $record): string => ($record->bahan_bakus_count + $record->product_count) > 0 ? 'Digunakan' : 'Belum digunakan')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Digunakan' ? 'success' : 'gray')
                    ->tooltip('Satuan yang digunakan oleh bahan baku atau produk tidak dapat dihapus.'),
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
                    ->placeholder('Semua satuan')
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
                    ->modalHeading('Hapus satuan')
                    ->modalDescription('Satuan yang belum digunakan akan dihapus. Satuan yang digunakan bahan baku atau produk dilindungi dari penghapusan.')
                    ->modalSubmitActionLabel('Ya, hapus satuan'),
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
            'index' => Pages\ListSatuans::route('/'),
            'create' => Pages\CreateSatuan::route('/create'),
            'edit' => Pages\EditSatuan::route('/{record}/edit'),
        ];
    }
}
