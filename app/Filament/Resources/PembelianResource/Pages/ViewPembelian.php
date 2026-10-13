<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Support\StockPresentation;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    public function boot(): void
    {
        app()->setLocale('id');
    }

    public function getTitle(): string
    {
        return 'Detail Penerimaan Bahan Baku';
    }

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()->label('Koreksi penerimaan')];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informasi penerimaan')
                ->schema([
                    Infolists\Components\TextEntry::make('pemasok.nama_pemasok')->label('Pemasok'),
                    Infolists\Components\TextEntry::make('tanggal_pembelian')->label('Tanggal diterima')->date('d M Y'),
                    Infolists\Components\TextEntry::make('catatan')->label('Catatan')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(2),
            Infolists\Components\Section::make('Bahan baku yang diterima')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('pembelianDetails')
                        ->hiddenLabel()
                        ->schema([
                            Infolists\Components\TextEntry::make('bahanBaku.nama_bahan')->label('Bahan baku'),
                            Infolists\Components\TextEntry::make('quantity')
                                ->label('Jumlah diterima')
                                ->formatStateUsing(fn ($state, $record): string => StockPresentation::quantity($state, $record->bahanBaku?->satuan?->nama_satuan)),
                        ])
                        ->columns(2),
                ]),
            Infolists\Components\Section::make('Riwayat pencatatan')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')->label('Dicatat pada')->dateTime('d M Y H:i'),
                    Infolists\Components\TextEntry::make('updated_at')->label('Terakhir dikoreksi')->dateTime('d M Y H:i'),
                ])
                ->columns(2),
        ]);
    }
}
