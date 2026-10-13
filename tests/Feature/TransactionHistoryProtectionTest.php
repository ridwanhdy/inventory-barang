<?php

use App\Filament\Resources\BahanBakuResource;
use App\Filament\Resources\KategoriResource;
use App\Filament\Resources\PemasokResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\SatuanResource;
use App\Filament\Resources\UserResource;
use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Pemasok;
use App\Models\Product;
use App\Models\Satuan;
use App\Models\User;
use App\Support\MasterDataUsage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
});

function historyProduct(array $attributes = []): Product
{
    $categoryId = $attributes['kategori_id'] ?? DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos']);
    $unitId = $attributes['satuan_id'] ?? DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);

    $id = DB::table('products')->insertGetId(array_replace([
        'nama_product' => 'Kaos hitam',
        'kategori_id' => $categoryId,
        'satuan_id' => $unitId,
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'harga_jual' => 50000,
        'foto' => '',
    ], $attributes));

    return Product::findOrFail($id);
}

function historyMaterial(array $attributes = []): BahanBaku
{
    $categoryId = $attributes['kategori_id'] ?? DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kain']);
    $unitId = $attributes['satuan_id'] ?? DB::table('satuans')->insertGetId(['nama_satuan' => 'meter']);

    $id = DB::table('bahan_bakus')->insertGetId(array_replace([
        'nama_bahan' => 'Katun hitam',
        'kategori_id' => $categoryId,
        'satuan_id' => $unitId,
        'jenis' => 'bahan baku',
    ], $attributes));

    return BahanBaku::findOrFail($id);
}

function historyMaster(string $resource): Model
{
    return match ($resource) {
        ProductResource::class => historyProduct(),
        BahanBakuResource::class => historyMaterial(),
        PemasokResource::class => Pemasok::findOrFail(DB::table('pemasoks')->insertGetId([
            'nama_pemasok' => 'Pemasok kain', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan',
        ])),
        SatuanResource::class => Satuan::findOrFail(DB::table('satuans')->insertGetId(['nama_satuan' => 'meter'])),
        KategoriResource::class => Kategori::findOrFail(DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos'])),
        UserResource::class => User::factory()->create(['role' => 'kasir']),
    };
}

function historyOrder(?int $userId = null): int
{
    return DB::table('orders')->insertGetId([
        'no_transaksi' => 'HISTORI/' . (DB::table('orders')->count() + 1),
        'nama_customer' => 'Pelanggan',
        'users_id' => $userId ?? auth()->id(),
        'tanggal_order' => '2026-10-13',
        'status_transaksi' => 'batal',
        'status_pembayaran' => 'belum_bayar',
        'subtotal' => 0,
        'total_harga' => 0,
    ]);
}

function historyProduction(?int $productId = null): int
{
    return DB::table('produksis')->insertGetId([
        'product_id' => $productId ?? historyProduct()->id,
        'jumlah_produksi' => 10,
        'produksi_mulai' => '2026-10-13',
        'status' => 'Batal',
    ]);
}

function historyReceipt(?int $supplierId = null): int
{
    return DB::table('pembelians')->insertGetId([
        'pemasok_id' => $supplierId ?? historyMaster(PemasokResource::class)->id,
        'tanggal_pembelian' => '2026-10-13',
    ]);
}

function historyReference(Model $record, string $reference): void
{
    if ($reference === 'penjualan') {
        DB::table('order_details')->insert([
            'order_id' => historyOrder(), 'product_id' => $record->id, 'quantity' => 1, 'harga' => 50000, 'subtotal' => 50000,
        ]);
    } elseif ($reference === 'produksi produk') {
        historyProduction($record->id);
    } elseif ($reference === 'penerimaan bahan') {
        DB::table('pembelian_details')->insert([
            'pembelian_id' => historyReceipt(), 'bahan_baku_id' => $record->id, 'quantity' => 10,
        ]);
    } elseif ($reference === 'produksi bahan') {
        DB::table('produksi_details')->insert([
            'produksi_id' => historyProduction(), 'bahan_baku_id' => $record->id, 'jumlah_digunakan' => 10,
        ]);
    } elseif ($reference === 'pemasok penerimaan') {
        historyReceipt($record->id);
    } elseif ($reference === 'satuan produk') {
        historyProduct(['satuan_id' => $record->id]);
    } elseif ($reference === 'satuan bahan') {
        historyMaterial(['satuan_id' => $record->id]);
    } elseif ($reference === 'kategori produk') {
        historyProduct(['kategori_id' => $record->id]);
    } elseif ($reference === 'kategori bahan') {
        historyMaterial(['kategori_id' => $record->id]);
    } elseif ($reference === 'petugas penjualan') {
        historyOrder($record->id);
    }
}

dataset('history master resources', [
    ProductResource::class,
    BahanBakuResource::class,
    PemasokResource::class,
    SatuanResource::class,
    KategoriResource::class,
    UserResource::class,
]);

dataset('history master references', [
    'produk dalam penjualan' => [ProductResource::class, 'penjualan'],
    'produk dalam produksi' => [ProductResource::class, 'produksi produk'],
    'bahan dalam penerimaan' => [BahanBakuResource::class, 'penerimaan bahan'],
    'bahan dalam produksi' => [BahanBakuResource::class, 'produksi bahan'],
    'pemasok dalam penerimaan' => [PemasokResource::class, 'pemasok penerimaan'],
    'satuan pada produk' => [SatuanResource::class, 'satuan produk'],
    'satuan pada bahan' => [SatuanResource::class, 'satuan bahan'],
    'kategori pada produk' => [KategoriResource::class, 'kategori produk'],
    'kategori pada bahan' => [KategoriResource::class, 'kategori bahan'],
    'petugas dalam penjualan' => [UserResource::class, 'petugas penjualan'],
]);

test('admin can delete unused master data while bulk deletion stays disabled', function (string $resource) {
    $record = historyMaster($resource);

    expect(MasterDataUsage::isUsed($record))->toBeFalse()
        ->and($resource::canDelete($record))->toBeTrue()
        ->and($resource::canDeleteAny())->toBeFalse();
})->with('history master resources');

test('master data referenced by history cannot be deleted including cancelled transactions', function (string $resource, string $reference) {
    $record = historyMaster($resource);
    historyReference($record, $reference);

    expect(MasterDataUsage::isUsed($record))->toBeTrue()
        ->and($resource::canDelete($record))->toBeFalse()
        ->and($resource::canDeleteAny())->toBeFalse();
})->with('history master references');

test('deleting a referenced master model rejects cascading removal of history', function (string $resource, string $reference) {
    $record = historyMaster($resource);
    historyReference($record, $reference);
    $table = $record->getTable();
    $counts = [];

    foreach (['orders', 'order_details', 'produksis', 'produksi_details', 'pembelians', 'pembelian_details'] as $historyTable) {
        $counts[$historyTable] = DB::table($historyTable)->count();
    }

    expect(fn () => $record->delete())->toThrow(ValidationException::class);
    $this->assertDatabaseHas($table, ['id' => $record->id]);

    foreach ($counts as $historyTable => $count) {
        $this->assertDatabaseCount($historyTable, $count);
    }
})->with('history master references');

test('unused master models remain individually removable', function (string $resource) {
    $record = historyMaster($resource);
    $table = $record->getTable();
    $id = $record->id;

    expect($record->delete())->toBeTrue();
    $this->assertDatabaseMissing($table, ['id' => $id]);
})->with('history master resources');

test('cashier cannot delete unused master data', function (string $resource) {
    $record = historyMaster($resource);
    $this->actingAs(User::factory()->create(['role' => 'kasir']));

    expect($resource::canDelete($record))->toBeFalse()
        ->and($resource::canDeleteAny())->toBeFalse();
})->with('history master resources');

test('admin cannot delete their own signed in account', function () {
    expect(MasterDataUsage::isUsed(auth()->user()))->toBeFalse()
        ->and(UserResource::canDelete(auth()->user()))->toBeFalse();

    $currentUser = auth()->user();
    expect(fn () => $currentUser->delete())->toThrow(ValidationException::class);
    $this->assertDatabaseHas('users', ['id' => $currentUser->id]);
});

test('guests cannot delete master data', function () {
    $record = historyMaster(ProductResource::class);
    auth()->logout();

    expect(ProductResource::canDelete($record))->toBeFalse();
});

test('stock without transaction history protects nonzero quantities but permits zero stock', function (string $resource, string $table, string $foreignKey) {
    $record = historyMaster($resource);
    DB::table($table)->insert([$foreignKey => $record->id, 'stok' => 0]);

    expect(MasterDataUsage::isUsed($record))->toBeFalse()
        ->and($resource::canDelete($record))->toBeTrue();

    foreach ([10, -2] as $stock) {
        DB::table($table)->where($foreignKey, $record->id)->update(['stok' => $stock]);

        expect(MasterDataUsage::isUsed($record))->toBeTrue()
            ->and($resource::canDelete($record))->toBeFalse();

        expect(fn () => $record->delete())->toThrow(ValidationException::class);
        $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
        $this->assertDatabaseHas($table, [$foreignKey => $record->id, 'stok' => $stock]);
    }
})->with([
    'stok produk' => [ProductResource::class, 'product_details', 'product_id'],
    'stok bahan' => [BahanBakuResource::class, 'bahan_baku_details', 'bahan_baku_id'],
]);
