<?php

namespace Database\Seeders;

use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Satuan;
use Database\Seeders\Support\DemoPeriod;
use Database\Seeders\Support\KaosPolosCatalog;
use Illuminate\Database\Seeder;

class BahanBakuSeeder extends Seeder
{
    public function run(): void
    {
        $units = Satuan::pluck('id', 'nama_satuan');
        $categories = Kategori::pluck('id', 'nama_kategori');
        $materials = [];

        foreach (KaosPolosCatalog::fabrics() as $fabric => $price) {
            foreach (KaosPolosCatalog::colors() as $color) {
                $materials[] = [KaosPolosCatalog::fabricName($fabric, $color), 'Meter', 'Kain'];
            }
        }

        foreach (KaosPolosCatalog::colors() as $color) {
            $materials[] = [KaosPolosCatalog::ribName($color), 'Meter', 'Kain'];
        }

        $materials = array_merge($materials, [
            ['Benang Jahit Polyester', 'Kg', 'Benang'],
            ['Label Ukuran Kaos', 'Pcs', 'Label'],
            ['Label Merek Kaos', 'Pcs', 'Label'],
            ['Plastik Kemasan Kaos', 'Pcs', 'Kemasan'],
        ]);

        DemoPeriod::at(DemoPeriod::start(), function () use ($materials, $units, $categories): void {
            foreach ($materials as [$name, $unit, $category]) {
                BahanBaku::updateOrCreate(
                    ['nama_bahan' => $name],
                    [
                        'satuan_id' => $units[$unit],
                        'kategori_id' => $categories[$category],
                        'jenis' => 'bahan baku',
                    ],
                )->forceFill([
                    'created_at' => DemoPeriod::start()->utc(),
                    'updated_at' => DemoPeriod::start()->utc(),
                ])->save();
            }
        });
    }
}
