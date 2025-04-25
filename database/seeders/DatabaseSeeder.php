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
            UserSeeder::class,
            SatuanSeeder::class,
            JenisSeeder::class,
            KategoriSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            PaymentSeeder::class,
        ]);
    }
}
