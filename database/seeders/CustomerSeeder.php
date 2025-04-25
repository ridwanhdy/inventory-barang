<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'nama' => 'Budi Santoso',
                'alamat' => 'Jl. Merdeka No. 123',
                'nomor_hp' => '081234567890',
            ],
            [
                'nama' => 'Ani Wijaya',
                'alamat' => 'Jl. Sudirman No. 45',
                'nomor_hp' => '082345678901',
            ],
            [
                'nama' => 'Dedi Kurniawan',
                'alamat' => 'Jl. Gatot Subroto No. 67',
                'nomor_hp' => '083456789012',
            ],
            [
                'nama' => 'Rina Fitriani',
                'alamat' => 'Jl. Diponegoro No. 89',
                'nomor_hp' => '084567890123',
            ],
            [
                'nama' => 'Eko Prasetyo',
                'alamat' => 'Jl. Thamrin No. 101',
                'nomor_hp' => '085678901234',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
} 