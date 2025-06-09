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
        
        // Get all customer IDs
        $customerIds = DB::table('customers')->pluck('id')->toArray();
        
        // Get a user ID (assuming there's at least one user)
        $userId = DB::table('users')->first()->id;
        
        // Generate orders for one year
        $startDate = Carbon::now()->subYear();
        $endDate = Carbon::now();
        
        while ($startDate <= $endDate) {
            // Generate 1-5 orders per day
            $ordersPerDay = rand(1, 5);
            
            for ($i = 0; $i < $ordersPerDay; $i++) {
                DB::table('orders')->insert([
                    'customer_id' => $customerIds[array_rand($customerIds)],
                    'users_id' => $userId,
                    'status_transaksi' => $statusTransaksi[array_rand($statusTransaksi)],
                    'status_pembayaran' => $statusPembayaran[array_rand($statusPembayaran)],
                    'metode_pembayaran' => $metodePembayaran[array_rand($metodePembayaran)],
                    'tanggal_order' => $startDate->format('Y-m-d'),
                    'created_at' => $startDate,
                    'updated_at' => $startDate,
                ]);
            }
            
            $startDate->addDay();
        }
    }
} 