<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PemasokResource\Pages;
use App\Models\Pemasok;
use App\Support\MasterDataUsage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PemasokResource extends Resource
{
    protected static ?string $model = Pemasok::class;

    protected static ?string $modelLabel = 'Pemasok';

    protected static ?string $pluralModelLabel = 'Pemasok';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $activeNavigationIcon = 'heroicon-s-truck';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Pemasok';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identitas dan kontak pemasok')
                    ->description('Simpan mitra penyedia bahan baku agar asal penerimaan dan kontak pemasok mudah ditelusuri.')
                    ->schema([
                        Forms\Components\TextInput::make('nama_pemasok')
                            ->label('Nama pemasok')
                            ->placeholder('Contoh: CV Sumber Kain')
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? '')),
                        Forms\Components\TextInput::make('nomor_telepon')
                            ->label('Nomor telepon')
                            ->placeholder('Contoh: 081234567890 atau +62 812 3456 7890')
                            ->tel()
                            ->required()
                            ->maxLength(20)
                            ->regex('/^(?=.*\d)[0-9+().\s-]{6,20}$/')
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->validationMessages(['regex' => 'Isi nomor telepon yang valid, 6-20 karakter berupa angka, spasi, +, -, atau tanda kurung.'])
                            ->helperText('Nomor dapat disalin langsung dari daftar pemasok.'),
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat lengkap')
                            ->placeholder('Nama jalan, nomor, kecamatan, kota, dan kode pos')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->mutateStateForValidationUsing(fn (?string $state): string => trim($state ?? ''))
                            ->dehydrateStateUsing(fn (?string $state): string => trim($state ?? ''))
                            ->columnSpanFull(),
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
            ->modifyQueryUsing(fn (Builder $query) => MasterDataUsage::withUsage($query)
                ->withCount('pembelians')
                ->withMax('pembelians', 'tanggal_pembelian'))
            ->description('Kontak pemasok dan riwayat penerimaan bahan baku. Pemasok yang sudah dipakai dalam transaksi tidak dapat dihapus.')
            ->searchPlaceholder('Cari nama, telepon, atau alamat')
            ->defaultSort('nama_pemasok')
            ->striped()
            ->paginationPageOptions([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('nama_pemasok')
                    ->label('Nama pemasok')
                    ->weight(FontWeight::Medium)
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_telepon')
                    ->label('Nomor telepon')
                    ->copyable()
                    ->copyMessage('Nomor telepon disalin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(50)
                    ->tooltip(fn (Pemasok $record): string => $record->alamat)
                    ->searchable(),
                Tables\Columns\TextColumn::make('pembelians_count')
                    ->label('Penerimaan')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->url(fn (Pemasok $record): string => PembelianResource::getUrl('index', [
                        'tableFilters' => ['pemasok' => ['value' => $record->getKey()]],
                    ]))
                    ->tooltip('Lihat riwayat penerimaan dari pemasok ini'),
                Tables\Columns\TextColumn::make('pembelians_max_tanggal_pembelian')
                    ->label('Penerimaan terakhir')
                    ->date('d M Y')
                    ->placeholder('Belum ada penerimaan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('digunakan')
                    ->label('Riwayat penerimaan')
                    ->placeholder('Semua pemasok')
                    ->trueLabel('Sudah memiliki penerimaan')
                    ->falseLabel('Belum memiliki penerimaan')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('pembelians'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('pembelians'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Pemasok tidak ditemukan')
            ->emptyStateDescription('Tambahkan pemasok pertama atau sesuaikan pencarian dan filter.');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPemasoks::route('/'),
            'create' => Pages\CreatePemasok::route('/create'),
            'view' => Pages\ViewPemasok::route('/{record}'),
            'edit' => Pages\EditPemasok::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
