<?php

namespace App\Filament\Resources\LaporanPenjualanResource\Pages;

use App\Filament\Resources\LaporanPenjualanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLaporanPenjualan extends ListRecords
{
    protected static string $resource = LaporanPenjualanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cetak_pdf')
                ->label('Cetak Semua Laporan Ke PDF')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    $records = $this->getFilteredTableQuery()->get();
                    
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.penjualan.pdf', [
                        'orders' => $records,
                        'totalPenjualan' => $records->sum('total_harga'),
                        'tanggalAwal' => $records->min('tanggal_order'),
                        'tanggalAkhir' => $records->max('tanggal_order'),
                    ]);
                    
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'laporan-penjualan.pdf');
                }),
        ];
    }
} 