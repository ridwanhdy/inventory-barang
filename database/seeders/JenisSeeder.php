<?php

namespace Database\Seeders;

use App\Models\Jenis;
use Illuminate\Database\Seeder;

class JenisSeeder extends Seeder
{
    public function run(): void
    {
        $jenis = [
            ['nama_jenis' => 'Bahan Baku'],
            ['nama_jenis' => 'Bahan Jadi'],
        ];

        foreach ($jenis as $item) {
            Jenis::create($item);
        }
    }
} 