<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-15 09:00:00', 'Asia/Jakarta'));
    $this->user = User::factory()->create(['role' => 'admin']);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function performanceDashboardProduct(): int
{
    return DB::table('products')->insertGetId([
        'nama_product' => 'Produk dashboard',
        'kategori_id' => DB::table('kategoris')->insertGetId(['nama_kategori' => 'Produk']),
        'satuan_id' => DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']),
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'harga_jual' => 100000,
        'foto' => 'product.jpg',
    ]);
}

function performanceDashboardOrder(int $userId, array $attributes = [], array $subtotals = []): int
{
    // Direct fixtures preserve stale totals and legacy orders without details for regression coverage.
    $id = DB::table('orders')->insertGetId(array_replace([
        'nama_customer' => 'Customer dashboard',
        'users_id' => $userId,
        'tanggal_order' => '2026-01-15',
        'status_transaksi' => 'selesai',
        'total_harga' => 100000,
    ], $attributes));

    if ($subtotals !== []) {
        $productId = performanceDashboardProduct();

        foreach ($subtotals as $subtotal) {
            DB::table('order_details')->insert([
                'order_id' => $id,
                'product_id' => $productId,
                'quantity' => 1,
                'harga' => $subtotal,
                'subtotal' => $subtotal,
            ]);
        }
    }

    return $id;
}

test('dashboard navigation renders before running widget summary queries', function () {
    $this->actingAs($this->user);

    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = $this->get('/admin');
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $response->assertOk()
        ->assertSee('Ringkasan bisnis Anda')
        ->assertSee('__lazyLoad')
        ->assertDontSee('Penjualan bulan ini');

    expect(collect($queries)->filter(fn (array $query): bool => str_contains($query['query'], 'dashboard_orders')
        || str_contains($query['query'], 'dashboard_payments')
        || str_contains($query['query'], 'dashboard_products')
        || str_contains($query['query'], 'monitored_stock')
    ))->toBeEmpty();
});

test('monthly metrics aggregate in SQL without hydrating orders and preserve date and total rules', function () {
    performanceDashboardOrder($this->user->id, ['tanggal_order' => '2025-08-01', 'total_harga' => 25000]);
    performanceDashboardOrder($this->user->id, ['tanggal_order' => '2025-07-31']);
    performanceDashboardOrder($this->user->id, ['tanggal_order' => '2026-02-01']);
    performanceDashboardOrder($this->user->id, ['total_harga' => 999999], [30000, 20000]);
    performanceDashboardOrder($this->user->id, ['total_harga' => 999999], [0]);
    performanceDashboardOrder($this->user->id, ['status_transaksi' => 'proses']);
    performanceDashboardOrder($this->user->id, ['status_transaksi' => 'batal']);

    $hydrated = 0;
    Event::listen('eloquent.retrieved: '.Order::class, function () use (&$hydrated) {
        $hydrated++;
    });

    DB::enableQueryLog();
    DB::flushQueryLog();
    $months = DashboardMetrics::monthlySummary();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect(array_column($months, 'sales'))->toBe([25000.0, 0.0, 0.0, 0.0, 0.0, 50000.0])
        ->and(array_column($months, 'orders'))->toBe([1, 0, 0, 0, 0, 3])
        ->and($hydrated)->toBe(0)
        ->and($queries)->toHaveCount(1);
});

test('outstanding metrics aggregate net payments without cancelled orders or negative balances', function () {
    $partial = performanceDashboardOrder($this->user->id, ['total_harga' => 999999], [30000, 20000]);
    $overpaid = performanceDashboardOrder($this->user->id, ['total_harga' => 10000]);
    performanceDashboardOrder($this->user->id, ['total_harga' => 12000]);
    performanceDashboardOrder($this->user->id, ['total_harga' => 999999], [0]);
    performanceDashboardOrder($this->user->id, ['status_transaksi' => 'batal']);

    DB::table('payments')->insert([
        ['order_id' => $partial, 'jumlah_bayar' => 20000, 'kembalian' => 5000, 'sisa_bayar' => 35000],
        ['order_id' => $partial, 'jumlah_bayar' => 10000, 'kembalian' => 0, 'sisa_bayar' => 25000],
        ['order_id' => $overpaid, 'jumlah_bayar' => 15000, 'kembalian' => 0, 'sisa_bayar' => 0],
    ]);

    $hydrated = 0;
    Event::listen('eloquent.retrieved: '.Order::class, function () use (&$hydrated) {
        $hydrated++;
    });

    expect(DashboardMetrics::outstandingPayments())->toBe(['amount' => 37000.0, 'orders' => 2])
        ->and($hydrated)->toBe(0);

    DB::table('payments')->insert([
        'order_id' => $partial, 'jumlah_bayar' => 25000, 'kembalian' => 0, 'sisa_bayar' => 0,
    ]);

    expect(DashboardMetrics::outstandingPayments())->toBe(['amount' => 12000.0, 'orders' => 1]);
});

test('product stock summary uses combined live detail stock without hydrating all products', function () {
    $available = performanceDashboardProduct();
    $netZero = performanceDashboardProduct();
    performanceDashboardProduct();

    DB::table('product_details')->insert([
        ['product_id' => $available, 'stok' => 3],
        ['product_id' => $available, 'stok' => -1],
        ['product_id' => $netZero, 'stok' => 1],
        ['product_id' => $netZero, 'stok' => -1],
    ]);

    $hydrated = 0;
    Event::listen('eloquent.retrieved: '.Product::class, function () use (&$hydrated) {
        $hydrated++;
    });

    expect(DashboardMetrics::productStockSummary())->toBe(['total' => 3, 'available' => 1])
        ->and($hydrated)->toBe(0);

    DB::table('product_details')->where('product_id', $available)->update(['stok' => 0]);

    expect(DashboardMetrics::productStockSummary())->toBe(['total' => 3, 'available' => 0]);
});
