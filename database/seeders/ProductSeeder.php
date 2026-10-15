<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Product;
use App\Models\Satuan;
use Database\Seeders\Support\DemoPeriod;
use Database\Seeders\Support\KaosPolosCatalog;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $category = Kategori::where('nama_kategori', 'Kaos Polos')->firstOrFail();
        $unit = Satuan::where('nama_satuan', 'Pcs')->firstOrFail();

        DemoPeriod::at(DemoPeriod::start(), function () use ($category, $unit): void {
            foreach (KaosPolosCatalog::fabrics() as $fabric => $basePrice) {
                foreach (KaosPolosCatalog::colors() as $color) {
                    foreach (KaosPolosCatalog::sizes() as $sizeIndex => $size) {
                        $sizeSurcharge = max(0, $sizeIndex - 4) * 5000;

                        Product::updateOrCreate(
                            ['nama_product' => "Kaos Polos {$fabric} - {$color} - {$size}"],
                            [
                                'kategori_id' => $category->id,
                                'satuan_id' => $unit->id,
                                'ukuran' => $size,
                                'warna' => $color,
                                'bahan' => $fabric,
                                'harga_jual' => $basePrice + $sizeSurcharge,
                                'foto' => '',
                            ],
                        )->forceFill([
                            'created_at' => DemoPeriod::start()->utc(),
                            'updated_at' => DemoPeriod::start()->utc(),
                        ])->save();
                    }
                }
            }
        });
    }
}
