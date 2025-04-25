<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        $satuans = [
            ['nama_satuan' => 'Pcs'],
            ['nama_satuan' => 'Kg'],
            ['nama_satuan' => 'Gram'],
            ['nama_satuan' => 'Liter'],
            ['nama_satuan' => 'Meter'],
        ];

        foreach ($satuans as $satuan) {
            Satuan::create($satuan);
        }
    }
} 