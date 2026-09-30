<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPenjualanResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class LaporanPenjualanResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

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
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_order')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('id')
                    ->label('No. Order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Penjualan')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
            ])
            ->defaultSort('tanggal_order', 'desc')
            ->filters([
                Tables\Filters\Filter::make('tanggal')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal_awal')
                                    ->label('Tanggal Awal')
                                    ->placeholder('Pilih tanggal awal'),
                                Forms\Components\DatePicker::make('tanggal_akhir')
                                    ->label('Tanggal Akhir')
                                    ->placeholder('Pilih tanggal akhir'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['tanggal_awal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_order', '>=', $date),
                            )
                            ->when(
                                $data['tanggal_akhir'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_order', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['tanggal_awal'] ?? null) {
                            $indicators['tanggal_awal'] = 'Tanggal Awal: ' . date('d/m/Y', strtotime($data['tanggal_awal']));
                        }
                        if ($data['tanggal_akhir'] ?? null) {
                            $indicators['tanggal_akhir'] = 'Tanggal Akhir: ' . date('d/m/Y', strtotime($data['tanggal_akhir']));
                        }
                        return $indicators;
                    })
                    ->columnSpanFull(),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->options([
                        'belum_bayar' => 'Belum Bayar',
                        'cicilan' => 'Cicilan',
                        'lunas' => 'Lunas',
                    ])
                    ->label('Status Pembayaran'),
            ])
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
                        ]);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-penjualan-' . $record->id . '.pdf');
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
                        ]);
                        
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-penjualan.pdf');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanPenjualan::route('/'),
            'create' => Pages\CreateLaporanPenjualan::route('/create'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status_transaksi', 'selesai');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
