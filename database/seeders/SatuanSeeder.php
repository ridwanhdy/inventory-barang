<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        $satuans = ['Pcs', 'Kg', 'Gram', 'Meter'];

        foreach ($satuans as $nama) {
            Satuan::firstOrCreate(['nama_satuan' => $nama]);
        }
    }
}
