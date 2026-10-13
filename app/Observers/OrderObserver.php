<?php

namespace App\Observers;

use App\Models\Order;
use App\Support\SalesStock;
use Illuminate\Validation\ValidationException;

class OrderObserver
{
    public function updating(Order $order): void
    {
        if (! $order->isDirty('status_transaksi')) {
            return;
        }
        if ($order->getOriginal('status_transaksi') !== 'proses' || ! in_array($order->status_transaksi, ['selesai', 'batal'], true)) {
            throw ValidationException::withMessages(['status_transaksi' => 'Penjualan yang sudah selesai atau dibatalkan tidak dapat dibuka kembali.']);
        }
        if ($order->status_transaksi === 'batal' && $order->payments()->exists()) {
            throw ValidationException::withMessages(['status_transaksi' => 'Penjualan yang memiliki pembayaran tidak dapat dibatalkan tanpa pengembalian uang.']);
        }
        if ($order->status_transaksi === 'selesai' && ! $order->orderDetails()->exists()) {
            throw ValidationException::withMessages(['status_transaksi' => 'Tambahkan barang sebelum menandai penjualan selesai.']);
        }
    }

    public function updated(Order $order): void
    {
        if ($order->wasChanged('status_transaksi') && $order->status_transaksi === 'batal') {
            $changes = [];
            foreach ($order->orderDetails()->get() as $detail) {
                $changes[$detail->product_id] = ($changes[$detail->product_id] ?? 0) + $detail->quantity;
            }
            SalesStock::change($changes);
        }
    }
}
