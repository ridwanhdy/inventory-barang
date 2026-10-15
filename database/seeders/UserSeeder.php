<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DemoPeriod::at(DemoPeriod::start(), function (): void {
            foreach ([
                ['name' => 'Admin', 'email' => 'admin@admin.com', 'password' => 'password', 'role' => 'admin'],
                ['name' => 'Kasir', 'email' => 'kasir@kasir.com', 'password' => 'kasir', 'role' => 'kasir'],
            ] as $account) {
                $user = User::updateOrCreate(
                    ['email' => $account['email']],
                    [
                        'name' => $account['name'],
                        'password' => Hash::make($account['password']),
                        'role' => $account['role'],
                    ],
                );

                $user->forceFill([
                    'email_verified_at' => DemoPeriod::start()->utc(),
                    'created_at' => DemoPeriod::start()->utc(),
                    'updated_at' => DemoPeriod::start()->utc(),
                ])->save();
            }
        });
    }
}
