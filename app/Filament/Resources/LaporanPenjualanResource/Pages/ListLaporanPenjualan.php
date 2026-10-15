<?php

namespace App\Filament\Resources\LaporanPenjualanResource\Pages;

use App\Filament\Resources\Concerns\InteractsWithReportPeriod;
use App\Filament\Resources\LaporanPenjualanResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanPenjualan extends ListRecords
{
    use InteractsWithReportPeriod;

    protected static string $resource = LaporanPenjualanResource::class;

    public function getSubheading(): string
    {
        return 'Pilih periode laporan dan pantau total penjualan. Cetak PDF untuk seluruh hasil filter atau transaksi yang dipilih.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->reportPdfAction('laporan.penjualan.pdf', 'laporan-penjualan.pdf', 'orders'),
        ];
    }
}
