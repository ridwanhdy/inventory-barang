<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        // Payment untuk order 1 (lunas) - Pembelian Kaos
        Payment::create([
            'order_id' => 1,
            'jumlah_bayar' => 420000, // (5 * 45000) + (3 * 65000)
            'sisa_bayar' => 0,
        ]);

        // Payment untuk order 2 (cicilan) - Pembelian Jaket
        Payment::create([
            'order_id' => 2,
            'jumlah_bayar' => 500000, // (2 * 150000) + (1 * 200000)
            'sisa_bayar' => 200000, // sisa yang belum dibayar
        ]);

        // Payment untuk order 3 (belum bayar) - Pembelian Varsity
        Payment::create([
            'order_id' => 3,
            'jumlah_bayar' => 500000, // 2 * 250000
            'sisa_bayar' => 500000, // belum dibayar sama sekali
        ]);
    }
} 