<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Order 1 - Pembelian Kaos
        $order1 = Order::create([
            'customer_id' => 1,
            'users_id' => 1,
            'status_transaksi' => 'selesai',
            'status_pembayaran' => 'lunas',
            'metode_pembayaran' => 'cash',
            'tanggal_order' => now()->subDays(5),
        ]);

        OrderDetail::create([
            'order_id' => $order1->id,
            'product_id' => 1, // Kaos Polos Premium
            'quantity' => 5,
            'harga' => 45000,
        ]);

        OrderDetail::create([
            'order_id' => $order1->id,
            'product_id' => 2, // Kaos Sablon Custom
            'quantity' => 3,
            'harga' => 65000,
        ]);

        // Order 2 - Pembelian Jaket
        $order2 = Order::create([
            'customer_id' => 2,
            'users_id' => 1,
            'status_transaksi' => 'proses',
            'status_pembayaran' => 'cicilan',
            'metode_pembayaran' => 'bank',
            'tanggal_order' => now()->subDays(3),
        ]);

        OrderDetail::create([
            'order_id' => $order2->id,
            'product_id' => 3, // Jaket Hoodie Basic
            'quantity' => 2,
            'harga' => 150000,
        ]);

        OrderDetail::create([
            'order_id' => $order2->id,
            'product_id' => 4, // Jaket Bomber Premium
            'quantity' => 1,
            'harga' => 200000,
        ]);

        // Order 3 - Pembelian Varsity
        $order3 = Order::create([
            'customer_id' => 3,
            'users_id' => 1,
            'status_transaksi' => 'batal',
            'status_pembayaran' => 'belum_bayar',
            'metode_pembayaran' => 'cash',
            'tanggal_order' => now()->subDays(1),
        ]);

        OrderDetail::create([
            'order_id' => $order3->id,
            'product_id' => 5, // Jaket Varsity Classic
            'quantity' => 2,
            'harga' => 250000,
        ]);
    }
} 