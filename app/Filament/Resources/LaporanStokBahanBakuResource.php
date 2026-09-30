<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanStokBahanBakuResource\Pages;
use App\Models\BahanBakuDetail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class LaporanStokBahanBakuResource extends Resource
{
    protected static ?string $model = BahanBakuDetail::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Stok Bahan Baku';
    protected static ?string $modelLabel = 'Laporan Stok Bahan Baku';
    protected static ?string $pluralModelLabel = 'Laporan Stok Bahan Baku';
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bahanBaku.nama_bahan')->label('Nama Bahan'),
                Tables\Columns\TextColumn::make('bahanBaku.kategori.nama_kategori')->label('Kategori'),
                Tables\Columns\TextColumn::make('bahanBaku.satuan.nama_satuan')->label('Satuan'),
                Tables\Columns\TextColumn::make('stok')->label('Stok')
                    ->badge()
                    ->color(function ($state) {
                        if ($state < 5) return 'danger';
                        if ($state < 10) return 'warning';
                        return 'success';
                    }),
            ])
            ->defaultSort('stok', 'asc')
            ->actions([
                Tables\Actions\Action::make('cetak_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-printer')
                    ->action(function (BahanBakuDetail $record) {
                        $pdf = Pdf::loadView('laporan.stok_bahan_baku.pdf', [
                            'details' => collect([$record]),
                        ]);
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-stok-bahan-baku-' . $record->id . '.pdf');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('cetak_pdf')
                    ->label('Cetak PDF Terpilih')
                    ->icon('heroicon-o-printer')
                    ->action(function (Collection $records) {
                        $pdf = Pdf::loadView('laporan.stok_bahan_baku.pdf', [
                            'details' => $records,
                        ]);
                        return response()->streamDownload(function () use ($pdf) {
                            echo $pdf->output();
                        }, 'laporan-stok-bahan-baku.pdf');
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanStokBahanBaku::route('/'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
