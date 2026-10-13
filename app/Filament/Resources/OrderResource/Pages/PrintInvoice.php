<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;

class PrintInvoice extends ViewRecord
{
    protected static string $resource = OrderResource::class;
    protected static ?string $title = 'Bukti penjualan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Cetak struk')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    $order = $this->record->refresh()->load(['orderDetails.product', 'payments']);
                    $totalHarga = $order->orderDetails->sum(function ($detail) {
                        return $detail->quantity * $detail->harga;
                    });
                    $totalBayar = $order->paidAmount();
                    
                    $pdf = PDF::loadView('pdf.invoice', [
                        'order' => $order,
                        'orderDetails' => $order->orderDetails,
                        'payments' => $order->payments,
                        'totalHarga' => $totalHarga,
                        'totalBayar' => $totalBayar,
                        'sisaBayar' => $order->remainingAmount(),
                        'totalKembalian' => $order->payments->sum('kembalian'),
                    ]);

                    return Response::streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'invoice-' . ($order->no_transaksi ? str_replace('/', '-', $order->no_transaksi) : $order->id) . '.pdf');
                }),
        ];
    }
}
