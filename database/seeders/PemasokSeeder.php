<?php

namespace Database\Seeders;

use App\Models\Pemasok;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class PemasokSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            $suppliers = [
                [
                    'nama_pemasok' => 'PT Demo Tekstil Nusantara',
                    'nomor_telepon' => '0812-0000-1001',
                    'alamat' => 'Jl. Contoh Tekstil No. 1, Bandung (data dummy)',
                ],
                [
                    'nama_pemasok' => 'CV Demo Rib dan Benang',
                    'nomor_telepon' => '0812-0000-1002',
                    'alamat' => 'Jl. Contoh Konveksi No. 2, Bandung (data dummy)',
                ],
                [
                    'nama_pemasok' => 'UD Demo Label dan Kemasan',
                    'nomor_telepon' => '0812-0000-1003',
                    'alamat' => 'Jl. Contoh Kemasan No. 3, Bandung (data dummy)',
                ],
            ];

            foreach ($suppliers as $supplier) {
                Pemasok::updateOrCreate(['nama_pemasok' => $supplier['nama_pemasok']], $supplier)
                    ->forceFill([
                        'created_at' => DemoPeriod::start()->utc(),
                        'updated_at' => DemoPeriod::start()->utc(),
                    ])->save();
            }
        });
    }
}
