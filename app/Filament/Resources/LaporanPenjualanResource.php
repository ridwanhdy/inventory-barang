<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPenjualanResource\Pages;
use App\Models\Order;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class LaporanPenjualanResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $activeNavigationIcon = 'heroicon-s-chart-bar-square';

    protected static ?string $navigationLabel = 'Laporan Penjualan';

    protected static ?string $modelLabel = 'Laporan Penjualan';

    protected static ?string $pluralModelLabel = 'Laporan Penjualan';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('tanggal_awal')
                    ->label('Tanggal Awal')
                    ->required(),
                Forms\Components\DatePicker::make('tanggal_akhir')
                    ->label('Tanggal Akhir')
                    ->required()
                    ->afterOrEqual('tanggal_awal'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Transaksi penjualan selesai')
            ->description('Ringkasan dan PDF mengikuti periode, status pembayaran, serta pencarian yang dipilih.')
            ->striped()
            ->searchPlaceholder('Cari nomor penjualan atau pelanggan')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_order')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_transaksi')
                    ->label('Nomor penjualan')
                    ->sortable()
                    ->searchable()
                    ->summarize(Summarizer::make('jumlah_transaksi')
                        ->label('Jumlah transaksi')
                        ->numeric(decimalPlaces: 0)
                        ->using(fn (QueryBuilder $query): int => $query->count())),
                Tables\Columns\TextColumn::make('nama_customer')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Penjualan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total penjualan')->money('IDR', locale: 'id')),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success',
                        'cicilan' => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
            ])
            ->defaultSort('tanggal_order', 'desc')
            ->filters([
                ReportPeriod::filter('tanggal_order'),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'cicilan' => 'Cicilan',
                        'lunas' => 'Lunas',
                    ])
                    ->placeholder('Semua status pembayaran')
                    ->label('Status Pembayaran'),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->filtersApplyAction(fn (Tables\Actions\Action $action) => $action
                ->label('Terapkan filter')
                ->icon('heroicon-o-funnel'))
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->action(function (Order $record) {
                        $pdf = Pdf::loadView('laporan.penjualan.pdf', [
                            'orders' => collect([$record]),
                            'totalPenjualan' => $record->total_harga,
                            'tanggalAwal' => $record->tanggal_order,
                            'tanggalAkhir' => $record->tanggal_order,
                            'periodeLabel' => $record->tanggal_order->format('d/m/Y'),
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-penjualan-'.$record->id.'.pdf');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('cetak_pdf')
                    ->label('Cetak PDF Terpilih')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records) {
                        $pdf = Pdf::loadView('laporan.penjualan.pdf', [
                            'orders' => $records,
                            'totalPenjualan' => $records->sum('total_harga'),
                            'tanggalAwal' => $records->min('tanggal_order'),
                            'tanggalAkhir' => $records->max('tanggal_order'),
                            'periodeLabel' => $records->min('tanggal_order')?->format('d/m/Y').' - '.$records->max('tanggal_order')?->format('d/m/Y'),
                            'filterLabel' => 'Transaksi terpilih',
                        ]);

                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-penjualan.pdf');
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->emptyStateHeading('Tidak ada penjualan pada periode ini')
            ->emptyStateDescription('Ubah periode, status pembayaran, atau pencarian. Laporan hanya memuat penjualan yang sudah selesai.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanPenjualan::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_transaksi', 'selesai');
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
