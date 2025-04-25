<?php

namespace Database\Seeders;

use App\Models\Jenis;
use Illuminate\Database\Seeder;

class JenisSeeder extends Seeder
{
    public function run(): void
    {
        $jenis = [
            ['nama_jenis' => 'Kaos'],
            ['nama_jenis' => 'Jaket'],
            ['nama_jenis' => 'Kemeja'],
            ['nama_jenis' => 'Celana'],
            ['nama_jenis' => 'Aksesoris'],
        ];

        foreach ($jenis as $item) {
            Jenis::create($item);
        }
    }
} 