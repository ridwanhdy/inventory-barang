<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $statusTransaksi = ['proses', 'batal', 'selesai'];
        $statusPembayaran = ['belum_bayar', 'cicilan', 'lunas'];
        $metodePembayaran = ['cash', 'bank'];
        $userId = \DB::table('users')->first()->id;
        $names = ['Ridwan', 'Budi', 'Siti', 'Agus', 'Dewi'];
        for ($i = 0; $i < 5; $i++) {
            \DB::table('orders')->insert([
                'nama_customer' => $names[$i],
                'users_id' => $userId,
                'status_transaksi' => $statusTransaksi[array_rand($statusTransaksi)],
                'status_pembayaran' => $statusPembayaran[array_rand($statusPembayaran)],
                'metode_pembayaran' => $metodePembayaran[array_rand($metodePembayaran)],
                'tanggal_order' => now()->subDays(5 - $i),
                'created_at' => now()->subDays(5 - $i),
                'updated_at' => now()->subDays(5 - $i),
            ]);
        }
    }
} 