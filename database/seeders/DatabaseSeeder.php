<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Master Data
            UserSeeder::class,
            SatuanSeeder::class,
            KategoriSeeder::class,
            PemasokSeeder::class,
            
            // Products & Bahan Baku
            ProductSeeder::class,
            BahanBakuSeeder::class,
            
            // Details (with observers)
            ProductDetailSeeder::class,
            BahanBakuDetailSeeder::class,
            
            // Transactions
            CustomerSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
            PembelianSeeder::class,
            
            // Production
            ProduksiSeeder::class,
        ]);
    }
}
