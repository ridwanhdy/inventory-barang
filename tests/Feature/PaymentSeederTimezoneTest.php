<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PaymentSeeder;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('demo payments preserve the stored order time under different clock timezones', function (string $clockTimezone) {
    $clock = Carbon::parse('2026-10-15 23:59:59', 'Asia/Jakarta')->setTimezone($clockTimezone);
    Carbon::setTestNow($clock);

    DemoPeriod::run(function () {
        $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos']);
        $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);
        $product = Product::create([
            'nama_product' => 'Kaos pengujian waktu pembayaran',
            'kategori_id' => $category,
            'satuan_id' => $unit,
            'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun',
            'harga_jual' => 30000, 'foto' => '',
        ]);
        $product->details()->firstOrFail()->update(['stok' => 1]);
        $cashier = User::factory()->create(['role' => 'kasir']);
        $orderedAt = DemoPeriod::day(29, 14, 11);

        $order = DemoPeriod::at($orderedAt, function () use ($cashier, $product, $orderedAt): Order {
            $order = Order::create([
                'no_transaksi' => 'DEMO-KAOS-'.$orderedAt->format('Ymd').'-002',
                'nama_customer' => 'Pelanggan pengujian waktu pembayaran',
                'users_id' => $cashier->id,
                'tanggal_order' => $orderedAt->toDateString(),
            ]);
            $order->orderDetails()->create([
                'product_id' => $product->id, 'quantity' => 1, 'harga' => $product->harga_jual,
            ]);

            return $order;
        });

        $this->seed(PaymentSeeder::class);

        expect($order->payments()->orderBy('id')->firstOrFail()->getRawOriginal('created_at'))
            ->toBe($orderedAt->utc()->addMinutes(20)->toDateTimeString())
            ->and(DB::table('payments')->join('orders', 'orders.id', '=', 'payments.order_id')
                ->whereColumn('payments.created_at', '<', 'orders.created_at')->exists())->toBeFalse();
    });

    expect(now($clockTimezone)->toDateTimeString())->toBe($clock->toDateTimeString());
})->with(['UTC', 'Asia/Jakarta', 'America/New_York']);
