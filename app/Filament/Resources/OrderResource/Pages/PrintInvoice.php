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
                    $pdf = Pdf::loadView('pdf.invoice', [
                        'order' => $order,
                        'orderDetails' => $order->orderDetails,
                        'customer' => $order->customer,
                    ]);

                    return Response::streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'invoice-' . $order->id . '.pdf');
                }),
        ];
    }
} 