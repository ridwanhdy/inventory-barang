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

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->action(function () {
                    $order = $this->record;
                    $totalHarga = $order->orderDetails->sum(function ($detail) {
                        return $detail->quantity * $detail->harga;
                    });
                    $totalBayar = $order->payments->sum('jumlah_bayar');
                    
                    $pdf = PDF::loadView('pdf.invoice', [
                        'order' => $order,
                        'customer' => $order->customer,
                        'orderDetails' => $order->orderDetails,
                        'payments' => $order->payments,
                        'totalHarga' => $totalHarga,
                        'totalBayar' => $totalBayar,
                        'sisaBayar' => max(0, $totalHarga - $totalBayar),
                    ]);

                    return Response::streamDownload(function () use ($pdf) {
                        echo $pdf->output();
                    }, 'invoice-' . $order->id . '.pdf');
                }),
        ];
    }
} 