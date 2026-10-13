<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderPrintController extends Controller
{
    public function printStruk($orderId)
    {
        abort_unless(auth()->check() && in_array(auth()->user()->role, ['admin', 'kasir'], true), 403);
        $order = Order::with(['orderDetails.product', 'payments'])->findOrFail($orderId);

        $totalHarga = $order->orderDetails->sum(function ($detail) {
            return $detail->quantity * $detail->harga;
        });
        $totalBayar = $order->paidAmount();

        $pdf = Pdf::loadView('pdf.invoice', [
            'order' => $order,
            'orderDetails' => $order->orderDetails,
            'payments' => $order->payments,
            'totalHarga' => $totalHarga,
            'totalBayar' => $totalBayar,
            'sisaBayar' => $order->remainingAmount(),
            'totalKembalian' => $order->payments->sum('kembalian'),
        ]);

        $filename = 'invoice-' . ($order->no_transaksi
            ? str_replace('/', '-', $order->no_transaksi)
            : $order->id) . '.pdf';

        return $pdf->stream($filename);
    }
}
