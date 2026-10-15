<?php

namespace Database\Seeders;

use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::run(function (): void {
            DB::transaction(function (): void {
                $this->call([
                    UserSeeder::class,
                    SatuanSeeder::class,
                    KategoriSeeder::class,
                    PemasokSeeder::class,
                    ProductSeeder::class,
                    BahanBakuSeeder::class,
                    ProductDetailSeeder::class,
                    BahanBakuDetailSeeder::class,
                    PembelianSeeder::class,
                    ProduksiSeeder::class,
                    BahanBakuHistorySeeder::class,
                    OrderSeeder::class,
                    PaymentSeeder::class,
                ]);
            });

            $this->command?->info('Dummy kaos polos: '.DemoPeriod::start()->format('d/m/Y').' - '.DemoPeriod::end()->format('d/m/Y').' (30 hari, WIB).');
        });
    }
}
