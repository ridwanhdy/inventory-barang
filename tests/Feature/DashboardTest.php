<?php

use App\Filament\Widgets\InventoryHealth;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\OrderPerMonth;
use App\Filament\Widgets\StatsOverview;
use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class DashboardPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 09:00:00', 'Asia/Jakarta'));
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-15 09:00:00', 'Asia/Jakarta'));
    Livewire::withoutLazyLoading();

    $user = User::factory()->create(['role' => 'admin']);
    $this->user = DashboardPanelUser::findOrFail($user->id);
    $this->actingAs($this->user);
});

afterEach(function () {
    Carbon::setTestNow();
    CarbonImmutable::setTestNow();
});

function dashboardProduct(string $name, array $stocks = []): int
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Produk']);
    $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);
    $product = DB::table('products')->insertGetId([
        'nama_product' => $name,
        'kategori_id' => $category,
        'satuan_id' => $unit,
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'stok' => 999,
        'harga_jual' => 100000,
        'foto' => 'product.jpg',
    ]);

    foreach ($stocks as $stock) {
        DB::table('product_details')->insert(['product_id' => $product, 'stok' => $stock]);
    }

    return $product;
}

function dashboardOrder(User $user, array $attributes = [], array $subtotals = []): Order
{
    // Persist fixtures directly because the existing Order events recalculate totals on save.
    $id = DB::table('orders')->insertGetId(array_replace([
        'no_transaksi' => 'TEST/'.(DB::table('orders')->count() + 1),
        'nama_customer' => 'Customer pengujian',
        'users_id' => $user->id,
        'tanggal_order' => '2026-01-15',
        'status_transaksi' => 'selesai',
        'status_pembayaran' => 'belum_bayar',
        'subtotal' => 0,
        'total_harga' => 0,
        'created_at' => '2025-07-01 12:00:00',
        'updated_at' => '2025-07-01 12:00:00',
    ], $attributes));

    if ($subtotals !== []) {
        $product = dashboardProduct('Produk order '.$id);

        foreach ($subtotals as $subtotal) {
            DB::table('order_details')->insert([
                'order_id' => $id,
                'product_id' => $product,
                'quantity' => 1,
                'harga' => $subtotal,
                'subtotal' => $subtotal,
            ]);
        }
    }

    return Order::findOrFail($id);
}

function dashboardPayment(Order $order, int $paid, int $remainingSnapshot): void
{
    DB::table('payments')->insert([
        'order_id' => $order->id,
        'jumlah_bayar' => $paid,
        'sisa_bayar' => $remainingSnapshot,
        'kembalian' => 0,
    ]);
}

function dashboardMaterial(string $name, string $unit, array $stocks, ?int $minimum = null): int
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Bahan baku']);
    $unitId = DB::table('satuans')->insertGetId(['nama_satuan' => $unit]);
    $material = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => $name,
        'satuan_id' => $unitId,
        'kategori_id' => $category,
    ]);

    foreach ($stocks as $stock) {
        $attributes = ['bahan_baku_id' => $material, 'stok' => $stock];

        if ($minimum !== null) {
            $attributes['stok_minimal'] = $minimum;
        }

        DB::table('bahan_baku_details')->insert($attributes);
    }

    return $material;
}

function dashboardProtectedData(object $widget, string $method): array
{
    return (fn () => $this->{$method}())->call($widget);
}

test('authenticated dashboard renders each widget once with empty data', function () {
    $response = $this->get('/admin')->assertOk()->assertSee('Ringkasan bisnis Anda');
    $html = $response->getContent();

    foreach (['Penjualan bulan ini', 'Order bulan ini', 'Produk tersedia', 'Sisa pembayaran', 'Tren penjualan', 'Pantauan inventaris', 'Penjualan terbaru'] as $heading) {
        expect(substr_count($html, $heading))->toBe(1);
    }

    $stats = collect(dashboardProtectedData(app(StatsOverview::class), 'getStats'))
        ->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($stats->all())->toBe([
        'Penjualan bulan ini' => 'Rp 0',
        'Order bulan ini' => '0',
        'Produk tersedia' => '0',
        'Sisa pembayaran' => 'Rp 0',
    ]);
});

test('dashboard requires authentication', function () {
    auth()->logout();

    $this->get('/admin')->assertRedirect('/admin/login');
});

test('summary uses transaction dates and line totals and sums payments without negative debt', function () {
    $completed = dashboardOrder($this->user, ['total_harga' => 999999], [200000, 300000]);
    dashboardPayment($completed, 200000, 300000);
    dashboardPayment($completed, 50000, 250000);
    dashboardOrder($this->user, ['status_transaksi' => 'proses', 'total_harga' => 200000]);

    $overpaid = dashboardOrder($this->user, ['total_harga' => 100000]);
    dashboardPayment($overpaid, 150000, 0);

    dashboardOrder($this->user, ['total_harga' => 777000], [0]);
    dashboardOrder($this->user, ['status_transaksi' => 'batal', 'total_harga' => 800000]);
    $older = dashboardOrder($this->user, ['tanggal_order' => '2025-12-31', 'total_harga' => 70000, 'created_at' => '2026-01-15 08:00:00']);
    dashboardPayment($older, 10000, 60000);

    dashboardProduct('Produk tersedia pertama', [1, 5]);
    dashboardProduct('Produk belum punya stok');
    dashboardProduct('Produk tersedia kedua', [0, 3]);
    dashboardProduct('Stok bersih nol', [-1, 1]);

    $stats = collect(dashboardProtectedData(app(StatsOverview::class), 'getStats'))
        ->mapWithKeys(fn ($stat) => [$stat->getLabel() => $stat->getValue()]);

    expect($stats['Penjualan bulan ini'])->toBe('Rp 600.000')
        ->and($stats['Order bulan ini'])->toBe('4')
        ->and($stats['Produk tersedia'])->toBe('2')
        ->and($stats['Sisa pembayaran'])->toBe('Rp 510.000');
});

test('monthly chart includes empty months and crosses a year using transaction dates', function () {
    dashboardOrder($this->user, ['tanggal_order' => '2025-08-01', 'total_harga' => 25000]);
    dashboardOrder($this->user, ['tanggal_order' => '2025-12-31', 'total_harga' => 50000]);
    dashboardOrder($this->user, ['tanggal_order' => '2026-01-01', 'total_harga' => 75000]);
    dashboardOrder($this->user, ['tanggal_order' => '2026-01-10', 'status_transaksi' => 'proses', 'total_harga' => 90000]);
    dashboardOrder($this->user, ['tanggal_order' => '2026-01-11', 'status_transaksi' => 'batal', 'total_harga' => 800000]);
    dashboardOrder($this->user, ['tanggal_order' => '2025-07-31', 'total_harga' => 999999, 'created_at' => '2026-01-15 12:00:00']);

    $chart = dashboardProtectedData(app(OrderPerMonth::class), 'getData');

    expect($chart['labels'])->toBe(['Agt 25', 'Sep 25', 'Okt 25', 'Nov 25', 'Des 25', 'Jan 26'])
        ->and($chart['datasets'][0]['data'])->toEqual([25000, 0, 0, 0, 50000, 75000]);
});

test('latest orders show only five recent transaction dates and handle payment statuses', function () {
    $older = dashboardOrder($this->user, ['nama_customer' => 'Order lama', 'tanggal_order' => '2025-12-01', 'created_at' => '2026-01-15 12:00:00']);
    $orders = collect();

    foreach (['belum_bayar', 'cicilan', 'lunas', 'cicilan', 'lunas'] as $index => $status) {
        $orders->push(dashboardOrder($this->user, [
            'nama_customer' => 'Customer '.$index,
            'tanggal_order' => '2026-01-'.str_pad((string) (10 + $index), 2, '0', STR_PAD_LEFT),
            'status_pembayaran' => $status,
        ]));
    }

    $widget = Livewire::test(LatestOrders::class)
        ->assertCanSeeTableRecords($orders->reverse(), inOrder: true)
        ->assertCanNotSeeTableRecords([$older])
        ->assertSee('Belum bayar')
        ->assertSee('Cicilan')
        ->assertSee('Lunas');

    expect($widget->instance()->getTable()->getColumn('status_pembayaran')->formatState('tak_dikenal'))
        ->toBe('Status tidak diketahui');
});

test('inventory groups stock per material and monitors exhausted stock without a configured minimum', function () {
    expect(Schema::hasColumn('bahan_baku_details', 'stok_minimal'))->toBeFalse();

    dashboardMaterial('Katun tersedia', 'meter', [0, 3]);
    dashboardMaterial('Benang habis', 'kg', [0, 0]);
    dashboardMaterial('Kain tersedia', 'meter', [0.75]);
    dashboardMaterial('Bahan tanpa catatan stok', 'kg', []);
    dashboardMaterial('Pewarna minus', 'kg', [-1]);

    $product = dashboardProduct('Produk dalam produksi');

    foreach (['Proses', 'Selesai', 'Batal'] as $status) {
        DB::table('produksis')->insert([
            'product_id' => $product,
            'jumlah_produksi' => 10,
            'produksi_mulai' => '2026-01-15',
            'status' => $status,
        ]);
    }

    $data = dashboardProtectedData(app(InventoryHealth::class), 'getViewData');

    expect($data['totalMaterials'])->toBe(4)
        ->and($data['lowStockCount'])->toBe(2)
        ->and($data['productionCount'])->toBe(1);

    Livewire::test(InventoryHealth::class)
        ->assertSee('Pemantauan stok habis')
        ->assertSee('Benang habis')
        ->assertSee('Pewarna minus')
        ->assertSee('kg')
        ->assertDontSee('Katun tersedia')
        ->assertDontSee('Kain tersedia');
});

test('inventory honors per-material minimum including the exact stock boundary', function () {
    Schema::table('bahan_baku_details', function (Blueprint $table) {
        $table->float('stok_minimal')->default(0);
    });

    dashboardMaterial('Katun batas minimum', 'meter', [2, 3], 5);
    dashboardMaterial('Benang aman', 'kg', [2, 4], 5);
    dashboardMaterial('Pewarna habis', 'kg', [0], 0);

    $data = dashboardProtectedData(app(InventoryHealth::class), 'getViewData');

    expect($data['totalMaterials'])->toBe(3)
        ->and($data['lowStockCount'])->toBe(2);

    Livewire::test(InventoryHealth::class)
        ->assertSee('Katun batas minimum')
        ->assertSee('Pewarna habis')
        ->assertSee('meter')
        ->assertSee('kg')
        ->assertDontSee('Benang aman');
});
