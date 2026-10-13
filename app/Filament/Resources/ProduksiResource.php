<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProduksiDetailRelationManagerResource\RelationManagers\ProduksiDetailRelationManager;
use App\Filament\Resources\ProduksiResource\Pages;
use App\Filament\Resources\ProduksiResource\ProductionActions;
use App\Models\BahanBaku;
use App\Models\Product;
use App\Models\Produksi;
use App\Support\StockPresentation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProduksiResource extends Resource
{
    protected static ?string $model = Produksi::class;

    protected static ?string $modelLabel = 'Produksi';

    protected static ?string $pluralModelLabel = 'Produksi';

    protected static ?string $navigationGroup = 'Manajemen Produksi';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Produksi';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny() && $record->fresh()?->isDraft();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function productLabel(Product $product): string
    {
        return $product->nama_product.' · '.$product->warna.' · '.$product->ukuran;
    }

    public static function materialFields(): array
    {
        return [
            Forms\Components\Select::make('bahan_baku_id')
                ->label('Bahan baku')
                ->relationship('bahanBaku', 'nama_bahan', fn (Builder $query) => $query->where('jenis', 'bahan baku')->with('satuan')->withSum('details', 'stok'))
                ->getOptionLabelFromRecordUsing(fn (BahanBaku $record): string => $record->nama_bahan.' · Stok '.StockPresentation::quantity($record->details_sum_stok ?? 0, $record->satuan?->nama_satuan))
                ->searchable()->preload()->live()->required()
                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('jumlah_digunakan')
                ->label('Jumlah untuk seluruh produksi')
                ->numeric()->minValue(0.01)->maxValue(999999.99)->step(0.01)
                ->rules(['decimal:0,2'])->required()
                ->suffix(fn (Forms\Get $get): string => BahanBaku::find($get('bahan_baku_id'))?->satuan?->nama_satuan ?? '')
                ->helperText('Isi total bahan untuk satu batch produksi. Contoh: 25,5 meter kain untuk 50 kaos.'),
            Forms\Components\Placeholder::make('stok_tersedia')
                ->label('Stok bahan saat ini')
                ->content(function (Forms\Get $get): string {
                    $material = BahanBaku::with('satuan')->withSum('details', 'stok')->find($get('bahan_baku_id'));

                    return $material ? StockPresentation::quantity($material->details_sum_stok ?? 0, $material->satuan?->nama_satuan) : 'Pilih bahan baku terlebih dahulu.';
                }),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\View::make('filament.resources.produksi-resource.workflow-guide')->columnSpanFull(),
            Forms\Components\Section::make('1. Rencana produksi')
                ->description('Tentukan varian dan target barang yang akan dibuat. Rencana belum mengurangi stok bahan baku.')
                ->schema([
                    Forms\Components\Select::make('product_id')
                        ->label('Produk, warna, dan ukuran')
                        ->relationship('product', 'nama_product', fn (Builder $query) => $query->with('satuan'))
                        ->getOptionLabelFromRecordUsing(fn (Product $record): string => static::productLabel($record))
                        ->searchable(['nama_product', 'warna', 'ukuran'])->preload()->required()->live(),
                    Forms\Components\TextInput::make('jumlah_produksi')
                        ->label('Target produksi')->integer()->minValue(1)->maxValue(999999)->required()
                        ->suffix(fn (Forms\Get $get): string => Product::find($get('product_id'))?->satuan?->nama_satuan ?? '')
                        ->helperText('Jumlah yang direncanakan. Hasil sebenarnya dicatat saat produksi selesai.'),
                    Forms\Components\DatePicker::make('produksi_mulai')
                        ->label('Rencana tanggal mulai')->default(today('Asia/Jakarta')->toDateString())->required(),
                    Forms\Components\Textarea::make('catatan')
                        ->label('Catatan produksi')->rows(2)->maxLength(5000)
                        ->placeholder('Contoh: pesanan 50 kaos hitam ukuran L, sablon bagian depan.'),
                ])->columns(2),
            Forms\Components\Section::make('2. Kebutuhan bahan baku')
                ->description('Masukkan total kebutuhan bahan. Ketersediaan stok diperiksa kembali ketika tombol Mulai produksi dijalankan.')
                ->schema([
                    Forms\Components\Repeater::make('produksiDetail')
                        ->label('Bahan untuk satu batch')->relationship()
                        ->schema(static::materialFields())->columns(2)
                        ->defaultItems(1)->minItems(1)->required()->reorderable(false)
                        ->addActionLabel('Tambah kebutuhan bahan'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Buka rincian produksi untuk memeriksa bahan, mencatat hasil, dan mengembalikan sisa bahan.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.satuan', 'produksiDetail']))
            ->defaultSort('id', 'desc')->striped()
            ->searchPlaceholder('Cari nomor produksi atau varian produk')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Nomor produksi')
                    ->formatStateUsing(fn ($state): string => 'PRD-'.str_pad((string) $state, 6, '0', STR_PAD_LEFT))
                    ->sortable()->searchable(query: function (Builder $query, string $search): Builder {
                        $number = preg_replace('/^PRD-0*/i', '', trim($search));

                        return ctype_digit($number) ? $query->where('id', (int) $number) : $query->whereRaw('1 = 0');
                    }),
                Tables\Columns\TextColumn::make('product.nama_product')->label('Produk dan varian')
                    ->description(fn (Produksi $record): string => ($record->product?->warna ?? '—').' · '.($record->product?->ukuran ?? '—'))
                    ->searchable(['nama_product', 'warna', 'ukuran']),
                Tables\Columns\TextColumn::make('jumlah_produksi')->label('Target')
                    ->formatStateUsing(fn ($state, Produksi $record): string => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan)),
                Tables\Columns\TextColumn::make('jumlah_hasil')->label('Hasil layak jual')->placeholder('Belum dicatat')
                    ->formatStateUsing(fn ($state, Produksi $record): string => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan)),
                Tables\Columns\TextColumn::make('status')->label('Tahap produksi')->badge()
                    ->formatStateUsing(fn (string $state): string => Produksi::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Produksi::PROCESS => 'warning', Produksi::COMPLETED => 'success', Produksi::CANCELLED => 'danger', default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('produksi_mulai')->label('Tanggal mulai')->date('d M Y')
                    ->description(fn (Produksi $record): ?string => ! $record->hasStarted() ? 'Rencana mulai' : null)->sortable(),
                Tables\Columns\TextColumn::make('produksi_selesai')->label('Tanggal selesai')->date('d M Y')
                    ->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Tahap produksi')->options(Produksi::statusLabels()),
                Tables\Filters\SelectFilter::make('product')->label('Produk')->relationship('product', 'nama_product')
                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => static::productLabel($record))->searchable()->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Rincian'),
                Tables\Actions\EditAction::make()->label('Ubah rencana'),
                ...ProductionActions::make(Tables\Actions\Action::class),
            ])->bulkActions([])
            ->emptyStateHeading('Belum ada produksi')
            ->emptyStateDescription('Buat rencana berisi produk, target, dan kebutuhan bahan. Mulai produksi setelah bahan siap.');
    }

    public static function getRelations(): array
    {
        return [ProduksiDetailRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProduksis::route('/'),
            'create' => Pages\CreateProduksi::route('/create'),
            'view' => Pages\ViewProduksi::route('/{record}'),
            'edit' => Pages\EditProduksi::route('/{record}/edit'),
        ];
    }
}
