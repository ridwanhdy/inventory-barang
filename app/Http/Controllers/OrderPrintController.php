<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPrintController extends Controller
{
    public function printStruk($orderId)
    {
        $order = Order::with(['orderDetails.product', 'payments'])->findOrFail($orderId);

        $totalHarga = $order->orderDetails->sum(function ($detail) {
            return $detail->quantity * $detail->harga;
        });
        $totalBayar = $order->payments->sum('jumlah_bayar');

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'orderDetails' => $order->orderDetails,
            'payments' => $order->payments,
            'totalHarga' => $totalHarga,
            'totalBayar' => $totalBayar,
            'sisaBayar' => max(0, $totalHarga - $totalBayar),
        ]);

        $filename = 'invoice-' . ($order->no_transaksi
            ? str_replace('/', '-', $order->no_transaksi)
            : $order->id) . '.pdf';

        return $pdf->stream($filename);
    }
}
