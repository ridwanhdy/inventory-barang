<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BahanBakuDetailResource\Pages;
use App\Models\BahanBakuDetail;
use App\Support\StockPresentation;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class BahanBakuDetailResource extends Resource
{
    protected static ?string $model = BahanBakuDetail::class;

    protected static ?string $modelLabel = 'Stok Bahan Baku';
    protected static ?string $pluralModelLabel = 'Stok Bahan Baku';

    protected static ?string $navigationLabel = 'Stok Bahan Baku';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationGroup = 'Stok';
    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('bahan_baku_id')
                    ->relationship('bahanBaku', 'nama_bahan')
                    ->live()
                    ->required(),
                Forms\Components\Placeholder::make('satuan')
                    ->label('Satuan')
                    ->content(fn (Forms\Get $get): string => \App\Models\BahanBaku::find($get('bahan_baku_id'))?->satuan?->nama_satuan ?? '—'),
                Forms\Components\Placeholder::make('kategori')
                    ->label('Kategori')
                    ->content(fn (Forms\Get $get): string => \App\Models\BahanBaku::find($get('bahan_baku_id'))?->kategori?->nama_kategori ?? '—'),
                TextInput::make('stok')->numeric()->step(0.01)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['bahanBaku.kategori', 'bahanBaku.satuan']))
            ->searchPlaceholder('Cari bahan baku')
            ->defaultSort('bahanBaku.nama_bahan')
            ->striped()
            ->columns([
                TextColumn::make('bahanBaku.nama_bahan')
                    ->label('Bahan baku')
                    ->description(fn (BahanBakuDetail $record) => $record->bahanBaku?->kategori?->nama_kategori ?? 'Tanpa kategori')
                    ->weight(FontWeight::Medium)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'bahanBaku',
                        fn (Builder $query) => $query->where('nama_bahan', 'like', "%{$search}%")
                            ->orWhereHas('kategori', fn (Builder $query) => $query->where('nama_kategori', 'like', "%{$search}%")),
                    ))
                    ->sortable()
                    ->wrap(),
                TextColumn::make('stok')
                    ->label('Jumlah stok')
                    ->formatStateUsing(fn ($state, BahanBakuDetail $record) => StockPresentation::quantity($state, $record->bahanBaku?->satuan?->nama_satuan))
                    ->weight(FontWeight::SemiBold)
                    ->sortable(),
                TextColumn::make('status_stok')
                    ->label('Ketersediaan')
                    ->getStateUsing(fn (BahanBakuDetail $record) => StockPresentation::status($record->stok, StockPresentation::minimum($record)))
                    ->badge()
                    ->color(fn (BahanBakuDetail $record) => StockPresentation::color($record->stok, StockPresentation::minimum($record)))
                    ->tooltip(fn (BahanBakuDetail $record) => $record->stok < 0 ? 'Stok negatif. Periksa pencatatan bahan masuk dan pemakaian produksi.' : null),
                TextColumn::make('bahanBaku.kategori.nama_kategori')
                    ->label('Kategori')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Terakhir diperbarui')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bahanBaku')
                    ->relationship('bahanBaku', 'nama_bahan')
                    ->label('Bahan baku')
                    ->placeholder('Semua bahan')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('kategori')
                    ->relationship('bahanBaku.kategori', 'nama_kategori')
                    ->label('Kategori')
                    ->placeholder('Semua kategori')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status_stok')
                    ->label('Ketersediaan')
                    ->placeholder('Semua status')
                    ->options(fn () => StockPresentation::statusOptions('bahan_baku_details'))
                    ->query(fn (Builder $query, array $data) => StockPresentation::filter($query, $data['value'] ?? null)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail stok'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Tidak ada bahan yang ditampilkan')
            ->emptyStateDescription('Ubah pencarian atau filter. Untuk bahan baru, tambahkan melalui Master Data Bahan Baku.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBahanBakuDetails::route('/'),
            'edit' => Pages\EditBahanBakuDetail::route('/{record}/edit'),
            'view' => Pages\ViewBahanBakuDetail::route('/{record}'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }
}
