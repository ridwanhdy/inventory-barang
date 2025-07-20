<?php

namespace App\Filament\Resources\LaporanStokBahanBakuResource\Pages;

use App\Filament\Resources\LaporanStokBahanBakuResource;
use App\Models\BahanBakuDetail;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;

class CreateLaporanStokBahanBaku extends CreateRecord
{
    protected static string $resource = LaporanStokBahanBakuResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $details = BahanBakuDetail::query()
            ->whereBetween('created_at', [$data['tanggal_awal'], $data['tanggal_akhir']])
            ->get();

        $pdf = Pdf::loadView('laporan.stok_bahan_baku.pdf', [
            'details' => $details,
            'tanggalAwal' => $data['tanggal_awal'],
            'tanggalAkhir' => $data['tanggal_akhir'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-stok-bahan-baku.pdf')->send();

        return $data;
    }
} 