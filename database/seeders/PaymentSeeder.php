<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            $totalHarga = $order->orderDetails->sum(function ($detail) {
                return $detail->quantity * $detail->harga;
            });

            // Create first payment (50% of total)
            $jumlahBayar1 = $totalHarga * 0.5;
            $sisaBayar1 = $totalHarga - $jumlahBayar1;
            $kembalian1 = 0;

            Payment::create([
                'order_id' => $order->id,
                'jumlah_bayar' => $jumlahBayar1,
                'sisa_bayar' => $sisaBayar1,
                'kembalian' => $kembalian1,
                'metode_pembayaran' => 'cash',
            ]);

            // Create second payment (remaining amount)
            $jumlahBayar2 = $sisaBayar1;
            $sisaBayar2 = 0;
            $kembalian2 = 0;

            Payment::create([
                'order_id' => $order->id,
                'jumlah_bayar' => $jumlahBayar2,
                'sisa_bayar' => $sisaBayar2,
                'kembalian' => $kembalian2,
                'metode_pembayaran' => 'bank',
            ]);

            // Update order status
            $order->update(['status_pembayaran' => 'lunas']);
        }
    }
} 