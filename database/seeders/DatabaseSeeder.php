<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Master data
            UserSeeder::class,
            SatuanSeeder::class,
            KategoriSeeder::class,
            PemasokSeeder::class,

            // Produk kaos & jaket + bahan baku
            ProductSeeder::class,
            BahanBakuSeeder::class,

            // Stok (update dari observer yang auto-create detail)
            ProductDetailSeeder::class,
            BahanBakuDetailSeeder::class,

            // Transaksi bahan baku & produksi
            PembelianSeeder::class,
            ProduksiSeeder::class,
        ]);
    }
}
