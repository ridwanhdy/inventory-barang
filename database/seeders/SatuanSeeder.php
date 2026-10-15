<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            foreach (['Pcs', 'Meter', 'Kg'] as $name) {
                Satuan::updateOrCreate(['nama_satuan' => $name])
                    ->forceFill([
                        'created_at' => DemoPeriod::start()->utc(),
                        'updated_at' => DemoPeriod::start()->utc(),
                    ])->save();
            }
        });
    }
}
