<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Carbon\Carbon;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $startDate = Carbon::create(null, 1, 1)->startOfDay(); // 1 Januari tahun ini
        $endDate = Carbon::create(null, 6, 30)->endOfDay();   // 30 Juni tahun ini

        while ($startDate <= $endDate) {
            DB::table('customers')->insert([
                'nama' => $faker->name,
                'nomor_hp' => $faker->phoneNumber,
                'alamat' => $faker->address,
                'created_at' => $startDate->copy(),
                'updated_at' => $startDate->copy(),
            ]);
            $startDate->addDay();
        }
    }
} 