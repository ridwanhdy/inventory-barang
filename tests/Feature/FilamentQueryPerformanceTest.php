<?php

use App\Filament\Resources\BahanBakuResource\Pages\ListBahanBakus;
use App\Filament\Resources\KategoriResource\Pages\ListKategoris;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\PemasokResource\Pages\ListPemasoks;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\SatuanResource\Pages\ListSatuans;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Pemasok;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\User;
use App\Support\MasterDataUsage;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function queryPerformanceProduct(array $attributes = []): Product
{
    $id = DB::table('products')->insertGetId(array_replace([
        'nama_product' => 'Produk pengujian',
        'kategori_id' => $attributes['kategori_id'] ?? DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kategori pengujian']),
        'satuan_id' => $attributes['satuan_id'] ?? DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']),
        'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun', 'harga_jual' => 50000, 'foto' => '',
    ], $attributes));

    return Product::findOrFail($id);
}

function queryPerformanceMaster(string $model): Model
{
    return match ($model) {
        Product::class => queryPerformanceProduct(),
        BahanBaku::class => BahanBaku::findOrFail(DB::table('bahan_bakus')->insertGetId([
            'nama_bahan' => 'Bahan pengujian',
            'kategori_id' => DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kain']),
            'satuan_id' => DB::table('satuans')->insertGetId(['nama_satuan' => 'meter']),
            'jenis' => 'bahan baku',
        ])),
        Kategori::class => Kategori::create(['nama_kategori' => 'Kategori pengujian']),
        Satuan::class => Satuan::create(['nama_satuan' => 'Satuan pengujian']),
        Pemasok::class => Pemasok::create(['nama_pemasok' => 'Pemasok pengujian', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan']),
        User::class => User::factory()->create(['role' => 'kasir']),
    };
}

function queryPerformanceReference(Model $record): void
{
    match (true) {
        $record instanceof Product => DB::table('product_details')->insert(['product_id' => $record->id, 'stok' => 3]),
        $record instanceof BahanBaku => DB::table('bahan_baku_details')->insert(['bahan_baku_id' => $record->id, 'stok' => -2]),
        $record instanceof Kategori => queryPerformanceProduct(['kategori_id' => $record->id]),
        $record instanceof Satuan => queryPerformanceProduct(['satuan_id' => $record->id]),
        $record instanceof Pemasok => DB::table('pembelians')->insert(['pemasok_id' => $record->id, 'tanggal_pembelian' => '2026-10-13']),
        $record instanceof User => DB::table('orders')->insert([
            'nama_customer' => 'Pelanggan', 'users_id' => $record->id, 'tanggal_order' => '2026-10-13',
        ]),
    };
}

function queryPerformanceListCount(string $page): int
{
    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        Livewire::test($page)->assertOk();

        return count(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
    }
}

dataset('query performance master lists', [
    'produk' => [Product::class, ListProducts::class],
    'bahan baku' => [BahanBaku::class, ListBahanBakus::class],
    'kategori' => [Kategori::class, ListKategoris::class],
    'satuan' => [Satuan::class, ListSatuans::class],
    'pemasok' => [Pemasok::class, ListPemasoks::class],
    'pengguna' => [User::class, ListUsers::class],
]);

test('master list query count does not grow with the number of rows', function (string $model, string $page) {
    queryPerformanceMaster($model);
    $singleRowQueries = queryPerformanceListCount($page);

    foreach (range(1, 8) as $index) {
        queryPerformanceMaster($model);
    }

    expect(queryPerformanceListCount($page))->toBeLessThanOrEqual($singleRowQueries + 2);
})->with('query performance master lists');

test('master table loads usage flags without per-record database reads', function (string $model, string $page) {
    $unused = queryPerformanceMaster($model);
    $used = queryPerformanceMaster($model);
    queryPerformanceReference($used);

    $component = Livewire::test($page);
    $records = $component->instance()->getFilteredTableQuery()->whereKey([$unused->id, $used->id])->get()->keyBy('id');

    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        expect(MasterDataUsage::isUsedForDisplay($records[$unused->id]))->toBeFalse()
            ->and(MasterDataUsage::isUsedForDisplay($records[$used->id]))->toBeTrue()
            ->and(DB::getQueryLog())->toBeEmpty();
    } finally {
        DB::disableQueryLog();
    }

    $component->assertTableActionVisible('delete', $unused)->assertTableActionHidden('delete', $used);
})->with('query performance master lists');

test('a stale table usage flag never bypasses the fresh deletion guard', function (string $model, string $page) {
    $record = queryPerformanceMaster($model);
    $snapshot = MasterDataUsage::withUsage($model::query())->findOrFail($record->id);
    expect(MasterDataUsage::isUsedForDisplay($snapshot))->toBeFalse();

    queryPerformanceReference($record);

    expect(MasterDataUsage::isUsed($snapshot))->toBeTrue()
        ->and(fn () => $snapshot->delete())->toThrow(ValidationException::class);
    $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
})->with('query performance master lists');

test('sales list aggregates totals and net payments without querying each sale', function () {
    $product = queryPerformanceProduct();
    $cases = [
        ['proses', 100000, [], 100000.0],
        ['proses', 100000, [[40000, 0]], 60000.0],
        ['selesai', 100000, [[40000, 0], [80000, 20000]], 0.0],
        ['batal', 100000, [], 0.0],
        ['proses', 0, [], 0.0],
    ];
    $records = [];

    foreach ($cases as [$status, $total, $payments, $remaining]) {
        $id = DB::table('orders')->insertGetId([
            'nama_customer' => 'Pelanggan '.$status, 'users_id' => auth()->id(), 'tanggal_order' => '2026-10-13',
            'status_transaksi' => $status, 'status_pembayaran' => 'belum_bayar', 'subtotal' => 999, 'total_harga' => 999,
        ]);

        if ($total > 0) {
            DB::table('order_details')->insert(['order_id' => $id, 'product_id' => $product->id, 'quantity' => 2, 'harga' => 50000, 'subtotal' => $total]);
        }

        foreach ($payments as [$received, $change]) {
            DB::table('payments')->insert(['order_id' => $id, 'jumlah_bayar' => $received, 'kembalian' => $change, 'sisa_bayar' => 0]);
        }

        $records[] = [$id, (float) $total, $remaining];
    }

    DB::enableQueryLog();
    DB::flushQueryLog();

    try {
        $component = Livewire::test(ListOrders::class)->assertOk();
        $queries = DB::getQueryLog();

        // A list render should never issue a separate relation query for a sale.
        expect(collect($queries)->filter(fn (array $query): bool => preg_match('/where (?:["`]?\w+["`]?\.)?["`]?order_id["`]? = \?/', $query['query'])))->toHaveCount(0);
    } finally {
        DB::disableQueryLog();
    }

    foreach ($records as [$id, $total, $remaining]) {
        $component->assertTableColumnStateSet('total_harga', $total, $id)
            ->assertTableColumnStateSet('sisa_tagihan', $remaining, $id);
    }
});
