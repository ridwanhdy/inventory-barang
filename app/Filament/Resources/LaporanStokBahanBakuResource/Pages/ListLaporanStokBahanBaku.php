<?php

namespace App\Filament\Resources\LaporanStokBahanBakuResource\Pages;

use App\Filament\Resources\LaporanStokBahanBakuResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanStokBahanBaku extends ListRecords
{
    protected static string $resource = LaporanStokBahanBakuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak_pdf')
                ->label('Cetak Semua Laporan Ke PDF')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.stok_bahan_baku.pdf', [
                        'details' => $records,
                        'tanggalAwal' => $records->min('created_at'),
                        'tanggalAkhir' => $records->max('created_at'),
                    ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'laporan-stok-bahan-baku.pdf');
                }),
        ];
    }
} 