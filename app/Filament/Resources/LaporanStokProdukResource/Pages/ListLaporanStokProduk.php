<?php

namespace App\Filament\Resources\LaporanStokProdukResource\Pages;

use App\Filament\Resources\LaporanStokProdukResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanStokProduk extends ListRecords
{
    protected static string $resource = LaporanStokProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak_pdf')
                ->label('Cetak Semua Laporan Ke PDF')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.stok_produk.pdf', [
                        'details' => $records,
                    ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'laporan-stok-produk.pdf');
                }),
        ];
    }
} 