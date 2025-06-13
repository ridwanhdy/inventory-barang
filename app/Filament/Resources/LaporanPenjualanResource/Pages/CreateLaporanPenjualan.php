<?php

namespace App\Filament\Resources\LaporanPenjualanResource\Pages;

use App\Filament\Resources\LaporanPenjualanResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Barryvdh\DomPDF\Facade\Pdf;

class CreateLaporanPenjualan extends CreateRecord
{
    protected static string $resource = LaporanPenjualanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $orders = Order::query()
            ->where('status_transaksi', 'selesai')
            ->whereBetween('tanggal_order', [$data['tanggal_awal'], $data['tanggal_akhir']])
            ->get();

        $pdf = Pdf::loadView('laporan.penjualan.pdf', [
            'orders' => $orders,
            'totalPenjualan' => $orders->sum('total_harga'),
            'tanggalAwal' => $data['tanggal_awal'],
            'tanggalAkhir' => $data['tanggal_akhir'],
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-penjualan.pdf')->send();

        return $data;
    }
} 