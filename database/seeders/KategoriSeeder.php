<?php

namespace Database\Seeders;

use App\Models\Kategori;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            foreach (['Kaos Polos', 'Kain', 'Benang', 'Label', 'Kemasan'] as $name) {
                Kategori::updateOrCreate(['nama_kategori' => $name])
                    ->forceFill([
                        'created_at' => DemoPeriod::start()->utc(),
                        'updated_at' => DemoPeriod::start()->utc(),
                    ])->save();
            }
        });
    }
}
