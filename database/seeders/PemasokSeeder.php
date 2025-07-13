<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Pemasok;

class PemasokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pemasoks = [
            [
                'nama_pemasok' => 'PT Sukses Jaya Textile',
                'nomor_telepon' => '021-5550123',
                'alamat' => 'Jl. Industri No. 45, Jakarta Barat',
            ],
            [
                'nama_pemasok' => 'CV Maju Bersama',
                'nomor_telepon' => '021-5550456',
                'alamat' => 'Jl. Raya Bekasi Km. 25, Bekasi',
            ],
            [
                'nama_pemasok' => 'UD Makmur Sejahtera',
                'nomor_telepon' => '021-5550789',
                'alamat' => 'Jl. Pasar Baru No. 12, Jakarta Pusat',
            ],
            [
                'nama_pemasok' => 'PT Global Supply',
                'nomor_telepon' => '021-5550321',
                'alamat' => 'Jl. Sudirman No. 78, Jakarta Selatan',
            ],
            [
                'nama_pemasok' => 'CV Mitra Abadi',
                'nomor_telepon' => '021-5550654',
                'alamat' => 'Jl. Gatot Subroto No. 34, Jakarta Selatan',
            ],
        ];

        foreach ($pemasoks as $pemasok) {
            Pemasok::create($pemasok);
        }

        $this->command->info('Pemasok data seeded successfully!');
    }
} 