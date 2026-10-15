<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanStokProdukResource\Pages;
use App\Models\ProductDetail;
use App\Support\ReportPeriod;
use App\Support\StockPresentation;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaporanStokProdukResource extends Resource
{
    protected static ?string $model = ProductDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Stok Produk';

    protected static ?string $modelLabel = 'Laporan Stok Produk';

    protected static ?string $pluralModelLabel = 'Laporan Stok Produk';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 8;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['product.kategori', 'product.satuan']))
            ->searchPlaceholder('Cari nama, varian, atau kategori produk')
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('product.nama_product')
                    ->label('Produk & varian')
                    ->description(fn (ProductDetail $record) => collect([
                        filled($record->product?->ukuran) ? 'Ukuran '.$record->product->ukuran : null,
                        $record->product?->warna,
                    ])->filter()->join(' · '))
                    ->weight(FontWeight::Medium)
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas(
                        'product',
                        fn (Builder $query) => $query->where('nama_product', 'like', "%{$search}%")
                            ->orWhere('ukuran', 'like', "%{$search}%")
                            ->orWhere('warna', 'like', "%{$search}%"),
                    ))
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('product.kategori.nama_kategori')
                    ->label('Kategori')
                    ->placeholder('Tanpa kategori')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Jumlah stok')
                    ->formatStateUsing(fn ($state, ProductDetail $record) => StockPresentation::quantity($state, $record->product?->satuan?->nama_satuan))
                    ->weight(FontWeight::SemiBold)
                    ->sortable()
                    ->summarize(Count::make()->label('Jumlah item')),
                Tables\Columns\TextColumn::make('status_stok')
                    ->label('Ketersediaan')
                    ->getStateUsing(fn (ProductDetail $record) => StockPresentation::status($record->stok, StockPresentation::minimum($record)))
                    ->badge()
                    ->color(fn (ProductDetail $record) => StockPresentation::color($record->stok, StockPresentation::minimum($record))),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Pembaruan stok (WIB)')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),
            ])
            ->defaultSort('stok', 'asc')
            ->filters([
                ReportPeriod::filter('updated_at'),
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
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->filtersApplyAction(fn (Tables\Actions\Action $action) => $action->label('Terapkan filter')->icon('heroicon-o-funnel'))
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->action(function (ProductDetail $record) {
                        $record->loadMissing(['product.kategori', 'product.satuan']);
                        $pdf = Pdf::loadView('laporan.stok_produk.pdf', [
                            'details' => collect([$record]),
                            'periodeLabel' => static::stockPeriodLabel(collect([$record])),
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-stok-produk-'.$record->id.'.pdf');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('cetak_pdf')
                    ->label('Cetak PDF Terpilih')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records) {
                        $records->loadMissing(['product.kategori', 'product.satuan']);
                        $pdf = Pdf::loadView('laporan.stok_produk.pdf', [
                            'details' => $records,
                            'periodeLabel' => static::stockPeriodLabel($records),
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-stok-produk.pdf');
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Tidak ada stok produk untuk laporan ini')
            ->emptyStateDescription('Ubah periode pembaruan stok, kategori, status, atau kata pencarian untuk menampilkan data.');
    }

    protected static function stockPeriodLabel(Collection $records): string
    {
        $dates = $records->pluck('updated_at')->filter();

        if ($dates->isEmpty()) {
            return 'Pembaruan stok: tanggal belum tercatat';
        }

        $start = Carbon::parse($dates->min())->timezone('Asia/Jakarta')->format('d/m/Y');
        $end = Carbon::parse($dates->max())->timezone('Asia/Jakarta')->format('d/m/Y');

        return 'Pembaruan stok: '.($start === $end ? $start : $start.' – '.$end);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanStokProduk::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
