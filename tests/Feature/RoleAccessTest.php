<?php

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\BahanBakuDetailResource\Pages\ViewBahanBakuDetail;
use App\Filament\Resources\BahanBakuResource;
use App\Filament\Resources\KategoriResource;
use App\Filament\Resources\LaporanPenjualanResource;
use App\Filament\Resources\LaporanPenjualanResource\Pages\ListLaporanPenjualan;
use App\Filament\Resources\LaporanStokBahanBakuResource;
use App\Filament\Resources\LaporanStokBahanBakuResource\Pages\ListLaporanStokBahanBaku;
use App\Filament\Resources\LaporanStokProdukResource;
use App\Filament\Resources\LaporanStokProdukResource\Pages\ListLaporanStokProduk;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PemasokResource;
use App\Filament\Resources\ProductDetailResource;
use App\Filament\Resources\ProductDetailResource\Pages\ViewProductDetail;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProduksiResource;
use App\Filament\Resources\SatuanResource;
use App\Filament\Resources\UserResource;
use App\Models\BahanBakuDetail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductDetail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

dataset('role restricted resources', [
    'pengguna' => UserResource::class,
    'produk' => ProductResource::class,
    'bahan baku' => BahanBakuResource::class,
    'kategori' => KategoriResource::class,
    'satuan' => SatuanResource::class,
    'pemasok' => PemasokResource::class,
    'produksi' => ProduksiResource::class,
    'penerimaan bahan' => PembelianResource::class,
]);

dataset('role shared resources', [
    'penjualan' => OrderResource::class,
    'stok produk' => ProductDetailResource::class,
    'stok bahan baku' => BahanBakuDetailResource::class,
    'laporan penjualan' => LaporanPenjualanResource::class,
    'laporan stok produk' => LaporanStokProdukResource::class,
    'laporan stok bahan baku' => LaporanStokBahanBakuResource::class,
]);

dataset('role stock resources', [
    'produk' => [ProductDetailResource::class, ViewProductDetail::class, 'produk'],
    'bahan baku' => [BahanBakuDetailResource::class, ViewBahanBakuDetail::class, 'bahan'],
]);

function roleAccessStock(string $kind): Model
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kategori akses']);
    $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);

    if ($kind === 'produk') {
        $product = DB::table('products')->insertGetId([
            'nama_product' => 'Produk akses', 'kategori_id' => $category, 'satuan_id' => $unit,
            'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun', 'harga_jual' => 50000, 'foto' => '',
        ]);

        return ProductDetail::findOrFail(DB::table('product_details')->insertGetId([
            'product_id' => $product, 'stok' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    $material = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => 'Bahan akses', 'kategori_id' => $category, 'satuan_id' => $unit, 'jenis' => 'bahan baku',
    ]);

    return BahanBakuDetail::findOrFail(DB::table('bahan_baku_details')->insertGetId([
        'bahan_baku_id' => $material, 'stok' => 10, 'created_at' => now(), 'updated_at' => now(),
    ]));
}

test('real admin and cashier accounts can enter the panel and dashboard', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    expect($user)->toBeInstanceOf(FilamentUser::class)
        ->and($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(['admin', 'kasir']);

test('admin can open every master and operational resource and its create page', function (string $resource) {
    $this->actingAs(User::factory()->create(['role' => 'admin']));

    expect($resource::canViewAny())->toBeTrue()
        ->and($resource::canCreate())->toBeTrue()
        ->and($resource::shouldRegisterNavigation())->toBeTrue();

    $this->get($resource::getUrl())->assertOk();
    $this->get($resource::getUrl('create'))->assertOk();
})->with('role restricted resources');

test('cashier cannot open master or operational pages by entering their URLs', function (string $resource) {
    $this->actingAs(User::factory()->create(['role' => 'kasir']));

    expect($resource::canViewAny())->toBeFalse()
        ->and($resource::canCreate())->toBeFalse()
        ->and($resource::shouldRegisterNavigation())->toBeFalse();

    $this->get($resource::getUrl())->assertForbidden();
    $this->get($resource::getUrl('create'))->assertForbidden();
})->with('role restricted resources');

test('admin and cashier can open sales stock and report pages with visible navigation', function (string $role, string $resource) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    expect($resource::canViewAny())->toBeTrue()
        ->and($resource::shouldRegisterNavigation())->toBeTrue();

    $this->get($resource::getUrl())->assertOk();
})->with(['admin', 'kasir'])->with('role shared resources');

test('cashier cannot edit another user or change their own role', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $other = User::factory()->create(['role' => 'admin']);
    $this->actingAs($cashier);

    foreach ([$cashier, $other] as $user) {
        expect(UserResource::canEdit($user))->toBeFalse()
            ->and(UserResource::canDelete($user))->toBeFalse();
        $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertForbidden();
    }
});

test('cashier can inspect stock details but cannot alter stock or master records', function (string $resource, string $page, string $kind) {
    $this->actingAs(User::factory()->create(['role' => 'kasir']));
    $record = roleAccessStock($kind);

    expect($resource::canView($record))->toBeTrue()
        ->and($resource::canCreate())->toBeFalse()
        ->and($resource::canEdit($record))->toBeFalse()
        ->and($resource::canDelete($record))->toBeFalse()
        ->and($resource::canDeleteAny())->toBeFalse();

    $this->get($resource::getUrl('view', ['record' => $record]))->assertOk();
    $this->get($resource::getUrl('edit', ['record' => $record]))->assertForbidden();
    Livewire::test($page, ['record' => $record->getRouteKey()])
        ->assertActionHidden('ubah_data_master');

    if (array_key_exists('create', $resource::getPages())) {
        $this->get($resource::getUrl('create'))->assertForbidden();
    }
})->with('role stock resources');

test('admin retains stock detail and edit access', function (string $resource, string $page, string $kind) {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $record = roleAccessStock($kind);

    expect($resource::canView($record))->toBeTrue()
        ->and($resource::canCreate())->toBeTrue()
        ->and($resource::canEdit($record))->toBeTrue();

    $this->get($resource::getUrl('view', ['record' => $record]))->assertOk();
    $this->get($resource::getUrl('edit', ['record' => $record]))->assertOk();
    Livewire::test($page, ['record' => $record->getRouteKey()])
        ->assertActionVisible('ubah_data_master');
})->with('role stock resources');

test('cashier can create sales and record payments without standalone payment management', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs($cashier);
    $stock = roleAccessStock('produk');
    $sale = Order::create([
        'nama_customer' => 'Pelanggan akses', 'users_id' => $cashier->id, 'tanggal_order' => '2026-10-15',
    ]);
    $sale->orderDetails()->create(['product_id' => $stock->product_id, 'quantity' => 1, 'harga' => 50000]);

    expect(OrderResource::canCreate())->toBeTrue()
        ->and(OrderResource::canEdit($sale))->toBeTrue()
        ->and(OrderResource::canDelete($sale))->toBeFalse()
        ->and(Gate::allows('viewAny', Payment::class))->toBeTrue()
        ->and(Gate::allows('create', Payment::class))->toBeTrue()
        ->and(PaymentResource::canViewAny())->toBeFalse();

    $this->get(OrderResource::getUrl('create'))->assertOk();
    $this->get(OrderResource::getUrl('edit', ['record' => $sale]))->assertOk();
    $this->get(PaymentResource::getUrl())->assertForbidden();
    $this->get(PaymentResource::getUrl('create'))->assertForbidden();

    Livewire::test(EditOrder::class, ['record' => $sale->getRouteKey()])
        ->callAction('bayar')->assertDispatched('open-sale-payment', orderId: $sale->id);
    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $sale->fresh(), 'pageClass' => EditOrder::class])
        ->callTableAction('create', data: ['jumlah_bayar' => 50000, 'metode_pembayaran' => 'cash'])
        ->assertHasNoTableActionErrors();

    $payment = $sale->payments()->sole();
    expect($sale->fresh()->status_pembayaran)->toBe('lunas')
        ->and(Gate::allows('delete', $payment))->toBeFalse()
        ->and(PaymentResource::canEdit($payment))->toBeFalse();
    $this->get(PaymentResource::getUrl('edit', ['record' => $payment]))->assertForbidden();
});

test('cashier can download all three reports', function (string $page, string $kind, string $filename) {
    $this->actingAs(User::factory()->create(['role' => 'kasir']));

    if ($kind === 'penjualan') {
        DB::table('orders')->insert([
            'no_transaksi' => 'AKSES/1', 'nama_customer' => 'Pelanggan laporan', 'users_id' => auth()->id(),
            'tanggal_order' => '2026-10-15', 'status_transaksi' => 'selesai', 'status_pembayaran' => 'lunas',
            'subtotal' => 50000, 'total_harga' => 50000,
        ]);
    } else {
        roleAccessStock($kind);
    }

    $pdf = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
    $pdf->shouldReceive('output')->once()->andReturn('%PDF-1.4 report access test');
    Pdf::shouldReceive('loadView')->once()->andReturn($pdf);

    Livewire::test($page)->set('tableDeferredFilters.periode', ['jenis' => 'all'])
        ->callAction('cetak_pdf')
        ->assertHasNoErrors()->assertFileDownloaded($filename);
})->with([
    'penjualan' => [ListLaporanPenjualan::class, 'penjualan', 'laporan-penjualan.pdf'],
    'stok produk' => [ListLaporanStokProduk::class, 'produk', 'laporan-stok-produk.pdf'],
    'stok bahan baku' => [ListLaporanStokBahanBaku::class, 'bahan', 'laporan-stok-bahan-baku.pdf'],
]);

test('unknown or missing roles cannot enter the panel or access resources', function (?string $role) {
    $user = User::factory()->create(['role' => 'kasir'])->forceFill(['role' => $role]);
    $this->actingAs($user);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(OrderResource::canViewAny())->toBeFalse()
        ->and(ProductDetailResource::canViewAny())->toBeFalse()
        ->and(LaporanPenjualanResource::canViewAny())->toBeFalse();

    $this->get('/admin')->assertForbidden();
    $this->get(OrderResource::getUrl())->assertForbidden();
    $this->get(UserResource::getUrl())->assertForbidden();
})->with(['unknown' => 'manager', 'missing' => null]);

test('guests must log in before opening dashboard or restricted and shared resources', function () {
    foreach (['/admin', UserResource::getUrl(), OrderResource::getUrl(), ProductDetailResource::getUrl(), LaporanPenjualanResource::getUrl()] as $url) {
        $this->get($url)->assertRedirect(route('filament.admin.auth.login'));
    }

    expect(UserResource::canViewAny())->toBeFalse()
        ->and(OrderResource::canCreate())->toBeFalse()
        ->and(ProductDetailResource::canViewAny())->toBeFalse();
});
