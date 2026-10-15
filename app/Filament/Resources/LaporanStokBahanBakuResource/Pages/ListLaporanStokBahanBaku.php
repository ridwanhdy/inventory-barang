<?php

namespace App\Filament\Resources\LaporanStokBahanBakuResource\Pages;

use App\Filament\Resources\Concerns\InteractsWithReportPeriod;
use App\Filament\Resources\LaporanStokBahanBakuResource;
use Filament\Resources\Pages\ListRecords;

class ListLaporanStokBahanBaku extends ListRecords
{
    use InteractsWithReportPeriod;

    protected static string $resource = LaporanStokBahanBakuResource::class;

    public function getSubheading(): ?string
    {
        return 'Periode mengikuti tanggal pembaruan stok terakhir. Jumlah yang ditampilkan adalah saldo stok saat ini, bukan saldo historis pada periode tersebut.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->reportPdfAction('laporan.stok_bahan_baku.pdf', 'laporan-stok-bahan-baku.pdf', 'details'),
        ];
    }
}
