<?php

namespace App\Filament\Resources\LaporanStokProdukResource\Pages;

use App\Filament\Resources\Concerns\InteractsWithReportPeriod;
use App\Filament\Resources\LaporanStokProdukResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanStokProduk extends ListRecords
{
    use InteractsWithReportPeriod;

    protected static string $resource = LaporanStokProdukResource::class;

    public function getSubheading(): ?string
    {
        return 'Periode mengikuti tanggal pembaruan stok terakhir. Jumlah yang ditampilkan adalah saldo stok saat ini, bukan saldo historis pada periode tersebut.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->reportPdfAction('laporan.stok_produk.pdf', 'laporan-stok-produk.pdf', 'details'),
        ];
    }
}
