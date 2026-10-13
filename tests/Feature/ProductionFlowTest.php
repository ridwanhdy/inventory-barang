<?php

use App\Filament\Resources\ProduksiDetailRelationManagerResource\RelationManagers\ProduksiDetailRelationManager;
use App\Filament\Resources\ProduksiResource;
use App\Filament\Resources\ProduksiResource\Pages\CreateProduksi;
use App\Filament\Resources\ProduksiResource\Pages\ListProduksis;
use App\Filament\Resources\ProduksiResource\Pages\ViewProduksi;
use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\Produksi;
use App\Models\ProduksiDetail;
use App\Models\User;
use App\Support\ProductionWorkflow;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class ProductionFlowPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'kasir'], true);
    }
}

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs(ProductionFlowPanelUser::findOrFail($user->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::withoutLazyLoading();
});

function productionFlowProduct(int $stock = 0): Product
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos produksi']);
    $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);
    $product = Product::create([
        'nama_product' => 'Kaos produksi '.Product::count(),
        'kategori_id' => $category, 'satuan_id' => $unit,
        'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun',
        'harga_jual' => 30000, 'foto' => 'test.jpg',
    ]);
    $product->details()->firstOrFail()->update(['stok' => $stock]);
    Product::whereKey($product->id)->update(['stok' => $stock]);

    return $product;
}

function productionFlowMaterial(string $name = 'Kain produksi', float $stock = 10, string $unit = 'meter'): BahanBaku
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Bahan produksi']);
    $unitId = DB::table('satuans')->insertGetId(['nama_satuan' => $unit]);
    $materialId = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => $name, 'kategori_id' => $category,
        'satuan_id' => $unitId, 'jenis' => 'bahan baku',
    ]);
    BahanBakuDetail::create(['bahan_baku_id' => $materialId, 'stok' => $stock]);

    return BahanBaku::findOrFail($materialId);
}

function productionFlowRecord(?Product $product = null, int $target = 10): Produksi
{
    return Produksi::create([
        'product_id' => ($product ?? productionFlowProduct())->id,
        'jumlah_produksi' => $target,
        'produksi_mulai' => today()->subDay()->toDateString(),
    ]);
}

function productionFlowStock(BahanBaku $material): float
{
    return (float) BahanBakuDetail::where('bahan_baku_id', $material->id)->sum('stok');
}

function productionFlowItem(Produksi $record, BahanBaku $material, float $quantity = 3): ProduksiDetail
{
    return $record->produksiDetail()->create([
        'bahan_baku_id' => $material->id, 'jumlah_digunakan' => $quantity,
    ]);
}

test('draft planning permits corrections and removal without consuming materials or adding finished stock', function () {
    $product = productionFlowProduct(4);
    $material = productionFlowMaterial(stock: 2);
    $record = productionFlowRecord($product);
    $item = productionFlowItem($record, $material, 2.5);

    expect($record->fresh()->status)->toBe(Produksi::DRAFT)
        ->and($record->fresh()->isDraft())->toBeTrue()
        ->and($record->fresh()->hasStarted())->toBeFalse()
        ->and(productionFlowStock($material))->toBe(2.0)
        ->and($product->details()->sum('stok'))->toEqual(4);

    $item->update(['jumlah_digunakan' => 1.75]);
    expect((float) $item->fresh()->jumlah_digunakan)->toBe(1.75)
        ->and(productionFlowStock($material))->toBe(2.0);
    $item->delete();
    $record->delete();

    expect(Produksi::count())->toBe(0)
        ->and(ProduksiDetail::count())->toBe(0)
        ->and(productionFlowStock($material))->toBe(2.0);
});

test('invalid planned material quantities and duplicate rows cannot change stock', function ($quantity) {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();

    expect(fn () => productionFlowItem($record, $material, $quantity))->toThrow(ValidationException::class);
    expect($record->produksiDetail()->count())->toBe(0)
        ->and(productionFlowStock($material))->toBe(10.0);
})->with([0, -1, 0.001]);

test('draft plans contain each material once and positive whole product targets', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    productionFlowItem($record, $material, 1);

    expect(fn () => productionFlowItem($record, $material, 2))->toThrow(ValidationException::class);
    foreach ([0, -1, 1.5] as $target) {
        expect(fn () => $record->fresh()->update(['jumlah_produksi' => $target]))->toThrow(ValidationException::class);
    }
    expect($record->fresh()->jumlah_produksi)->toEqual(10)
        ->and($record->produksiDetail()->count())->toBe(1)
        ->and(productionFlowStock($material))->toBe(10.0);
});

test('starting validates every material and rolls back the entire issue when stock is insufficient', function () {
    $first = productionFlowMaterial('Kain tersedia', 2);
    BahanBakuDetail::create(['bahan_baku_id' => $first->id, 'stok' => 0.5]);
    $second = productionFlowMaterial('Benang kurang', 1, 'kg');
    $record = productionFlowRecord();
    productionFlowItem($record, $first, 2.5);
    productionFlowItem($record, $second, 2);

    expect(fn () => ProductionWorkflow::start($record, today()->toDateString()))->toThrow(ValidationException::class);
    expect($record->fresh()->status)->toBe(Produksi::DRAFT)
        ->and(productionFlowStock($first))->toBe(2.5)
        ->and(productionFlowStock($second))->toBe(1.0);

    BahanBakuDetail::where('bahan_baku_id', $second->id)->update(['stok' => 3]);
    $stale = $record->fresh();
    ProductionWorkflow::start($record->fresh(), today()->toDateString());
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and($record->fresh()->hasStarted())->toBeTrue()
        ->and(productionFlowStock($first))->toBe(0.0)
        ->and(productionFlowStock($second))->toBe(1.0);

    expect(fn () => ProductionWorkflow::start($stale, today()->toDateString()))->toThrow(ValidationException::class);
    expect(productionFlowStock($second))->toBe(1.0);
});

test('a plan cannot start without materials or with a future actual start date', function () {
    $record = productionFlowRecord();
    expect(fn () => ProductionWorkflow::start($record, today()->toDateString()))->toThrow(ValidationException::class);

    $material = productionFlowMaterial();
    productionFlowItem($record, $material);
    expect(fn () => ProductionWorkflow::start($record->fresh(), today()->addDay()->toDateString()))->toThrow(ValidationException::class);
    expect($record->fresh()->status)->toBe(Produksi::DRAFT)
        ->and(productionFlowStock($material))->toBe(10.0);
});

test('processing allows a validated additional material issue and locks previously issued rows', function () {
    $material = productionFlowMaterial();
    $extra = productionFlowMaterial('Benang tambahan', 1, 'kg');
    $record = productionFlowRecord();
    $issued = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());

    expect(fn () => productionFlowItem($record->fresh(), $extra, 2))->toThrow(ValidationException::class);
    expect(productionFlowStock($extra))->toBe(1.0)
        ->and($record->produksiDetail()->count())->toBe(1);

    productionFlowItem($record->fresh(), $extra, 0.5);
    expect(productionFlowStock($extra))->toBe(0.5);
    expect(fn () => $issued->update(['jumlah_digunakan' => 1]))->toThrow(ValidationException::class);
    expect(fn () => $issued->fresh()->delete())->toThrow(ValidationException::class);
    expect((float) $issued->fresh()->jumlah_digunakan)->toBe(3.0)
        ->and(productionFlowStock($material))->toBe(7.0);
});

test('additional issues of the same material debit only the increment and preserve one material history row', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    expect(fn () => ProductionWorkflow::issue($record, $material->id, 1))->toThrow(ValidationException::class);
    ProductionWorkflow::start($record, today()->toDateString());

    $issued = ProductionWorkflow::issue($record, $material->id, 1.25);
    expect($issued->id)->toBe($item->id)
        ->and((float) $item->fresh()->jumlah_digunakan)->toBe(4.25)
        ->and($record->produksiDetail()->count())->toBe(1)
        ->and(productionFlowStock($material))->toBe(5.75);

    expect(fn () => ProductionWorkflow::issue($record, $material->id, 6))->toThrow(ValidationException::class);
    expect((float) $item->fresh()->jumlah_digunakan)->toBe(4.25)
        ->and(productionFlowStock($material))->toBe(5.75);
    ProductionWorkflow::complete($record, 10, 0, today()->toDateString(), null, [$item->id => 0.25]);
    expect(productionFlowStock($material))->toBe(6.0);
    expect(fn () => ProductionWorkflow::issue($record, $material->id, 1))->toThrow(ValidationException::class);
});

test('started product target dates and status cannot bypass the workflow', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    productionFlowItem($record, $material);
    ProductionWorkflow::start($record, today()->toDateString());
    $otherProduct = productionFlowProduct();

    foreach ([
        ['jumlah_produksi' => 20], ['product_id' => $otherProduct->id],
        ['status' => Produksi::COMPLETED], ['status' => Produksi::CANCELLED],
    ] as $change) {
        expect(fn () => $record->fresh()->update($change))->toThrow(ValidationException::class);
    }
    expect(fn () => $record->fresh()->delete())->toThrow(ValidationException::class);
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and(productionFlowStock($material))->toBe(7.0);
});

test('completion credits only accepted output once and returns only explicitly unused materials', function () {
    $product = productionFlowProduct(4);
    $material = productionFlowMaterial();
    $record = productionFlowRecord($product);
    $item = productionFlowItem($record, $material, 3.5);
    ProductionWorkflow::start($record, today()->toDateString());
    $stale = $record->fresh();

    ProductionWorkflow::complete($record, 8, 2, today()->toDateString(), null, [$item->id => 0.5]);

    expect($record->fresh()->status)->toBe(Produksi::COMPLETED)
        ->and($record->fresh()->jumlah_hasil)->toEqual(8)
        ->and($record->fresh()->jumlah_rusak)->toEqual(2)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(0.5)
        ->and(productionFlowStock($material))->toBe(7.0)
        ->and($product->details()->sum('stok'))->toEqual(12)
        ->and($product->fresh()->stok)->toEqual(12);

    expect(fn () => ProductionWorkflow::complete($stale, 8, 2, today()->toDateString(), null, [$item->id => 0.5]))
        ->toThrow(ValidationException::class);
    expect($product->details()->sum('stok'))->toEqual(12)
        ->and(productionFlowStock($material))->toBe(7.0);
});

test('completion rejects excessive or unrelated returns without recording any output', function () {
    $product = productionFlowProduct(4);
    $material = productionFlowMaterial();
    $record = productionFlowRecord($product);
    $item = productionFlowItem($record, $material, 3);
    $foreignItem = productionFlowItem(productionFlowRecord(), $material, 1);
    ProductionWorkflow::start($record, today()->toDateString());

    foreach ([[$item->id => 4], [$foreignItem->id => 0.5], [$item->id => 0.001]] as $returns) {
        expect(fn () => ProductionWorkflow::complete($record->fresh(), 10, 0, today()->toDateString(), null, $returns))
            ->toThrow(ValidationException::class);
    }

    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(0.0)
        ->and(productionFlowStock($material))->toBe(7.0)
        ->and($product->details()->sum('stok'))->toEqual(4)
        ->and($product->fresh()->stok)->toEqual(4);
});

test('failure to credit finished output rolls back material returns and their audit values', function () {
    $product = productionFlowProduct(999999);
    $material = productionFlowMaterial();
    $record = productionFlowRecord($product);
    $item = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());

    expect(fn () => ProductionWorkflow::complete($record, 10, 0, today()->toDateString(), null, [$item->id => 1]))
        ->toThrow(ValidationException::class);
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and(productionFlowStock($material))->toBe(7.0)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(0.0)
        ->and($product->details()->sum('stok'))->toEqual(999999)
        ->and($product->fresh()->stok)->toEqual(999999);
});

test('completion requires an explanation for changed output and a valid actual completion date', function () {
    $product = productionFlowProduct();
    $material = productionFlowMaterial();
    $record = productionFlowRecord($product);
    $record->update(['catatan' => 'Rencana untuk pesanan toko.']);
    productionFlowItem($record, $material);
    ProductionWorkflow::start($record, today()->toDateString());

    expect(fn () => ProductionWorkflow::complete($record->fresh(), 7, 1, today()->toDateString()))
        ->toThrow(ValidationException::class);
    foreach ([today()->subDay()->toDateString(), today()->addDay()->toDateString()] as $date) {
        expect(fn () => ProductionWorkflow::complete($record->fresh(), 10, 0, $date))
            ->toThrow(ValidationException::class);
    }
    expect(fn () => ProductionWorkflow::complete($record->fresh(), -1, 11, today()->toDateString()))
        ->toThrow(ValidationException::class);
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and($product->details()->sum('stok'))->toEqual(0);

    ProductionWorkflow::complete($record->fresh(), 7, 1, today()->toDateString(), 'Dua potong tidak selesai karena bahan cacat.');
    expect($record->fresh()->jumlah_hasil)->toEqual(7)
        ->and($record->fresh()->catatan_hasil)->toContain('bahan cacat')
        ->and($record->fresh()->catatan)->toBe('Rencana untuk pesanan toko.')
        ->and($product->details()->sum('stok'))->toEqual(7);
});

test('cancellation retains consumed material and restores only explicit returns while preserving history', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());

    expect(fn () => ProductionWorkflow::cancel($record->fresh(), ''))->toThrow(ValidationException::class);
    $stale = $record->fresh();
    ProductionWorkflow::cancel($record, 'Mesin mengalami kerusakan.', [$item->id => 1]);

    expect($record->fresh()->status)->toBe(Produksi::CANCELLED)
        ->and($record->fresh()->alasan_batal)->toBe('Mesin mengalami kerusakan.')
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(1.0)
        ->and(productionFlowStock($material))->toBe(8.0)
        ->and($record->produksiDetail()->count())->toBe(1);

    expect(fn () => ProductionWorkflow::cancel($stale, 'Ulangi.', [$item->id => 1]))->toThrow(ValidationException::class);
    expect(fn () => $record->fresh()->delete())->toThrow(ValidationException::class);
    expect(fn () => $item->fresh()->update(['jumlah_digunakan' => 1]))->toThrow(ValidationException::class);
    expect(fn () => productionFlowItem($record->fresh(), productionFlowMaterial('Bahan baru')))->toThrow(ValidationException::class);
    expect(productionFlowStock($material))->toBe(8.0);
});

test('cancelling draft or consumed production does not manufacture an automatic material refund', function () {
    $material = productionFlowMaterial();
    $draft = productionFlowRecord();
    $draftItem = productionFlowItem($draft, $material);
    expect(fn () => ProductionWorkflow::cancel($draft->fresh(), 'Tidak dijalankan.', [$draftItem->id => 1]))
        ->toThrow(ValidationException::class);
    ProductionWorkflow::cancel($draft, 'Pesanan tidak jadi.');
    expect(productionFlowStock($material))->toBe(10.0)
        ->and($draft->fresh()->status)->toBe(Produksi::CANCELLED)
        ->and($draft->fresh()->hasStarted())->toBeFalse();

    $processing = productionFlowRecord();
    productionFlowItem($processing, $material);
    ProductionWorkflow::start($processing, today()->toDateString());
    ProductionWorkflow::cancel($processing, 'Bahan telah digunakan, hasil dibuang.');
    expect(productionFlowStock($material))->toBe(7.0)
        ->and($processing->product->details()->sum('stok'))->toEqual(0);
});

test('closed production output notes and materials remain immutable', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material);
    ProductionWorkflow::start($record, today()->toDateString());
    ProductionWorkflow::complete($record, 10, 0, today()->toDateString());

    foreach ([['jumlah_hasil' => 20], ['catatan' => 'Ganti riwayat'], ['status' => Produksi::DRAFT]] as $change) {
        expect(fn () => $record->fresh()->update($change))->toThrow(ValidationException::class);
    }
    expect(fn () => $item->fresh()->delete())->toThrow(ValidationException::class);
    expect(fn () => $record->fresh()->delete())->toThrow(ValidationException::class);
    expect($record->fresh()->isClosed())->toBeTrue()
        ->and($record->product->details()->sum('stok'))->toEqual(10)
        ->and(productionFlowStock($material))->toBe(7.0);
});

test('completion creates missing finished stock rows and synchronizes aggregate stock', function () {
    $product = productionFlowProduct();
    $product->details()->delete();
    $material = productionFlowMaterial();
    $record = productionFlowRecord($product);
    productionFlowItem($record, $material);
    ProductionWorkflow::start($record, today()->toDateString());
    ProductionWorkflow::complete($record, 10, 0, today()->toDateString());
    expect($product->details()->sum('stok'))->toEqual(10)
        ->and($product->fresh()->stok)->toEqual(10);

    ProductDetail::create(['product_id' => $product->id, 'stok' => 3]);
    $next = productionFlowRecord($product, 2);
    productionFlowItem($next, $material, 1);
    ProductionWorkflow::start($next, today()->toDateString());
    ProductionWorkflow::complete($next, 2, 0, today()->toDateString());
    expect($product->details()->sum('stok'))->toEqual(15)
        ->and($product->fresh()->stok)->toEqual(15);
});

test('production draft form saves target and fractional planned materials together without changing stock', function () {
    $product = productionFlowProduct(4);
    $material = productionFlowMaterial(stock: 1);

    Livewire::test(CreateProduksi::class)
        ->fillForm(fn (array $state): array => [
            'product_id' => $product->id,
            'jumlah_produksi' => 10,
            'produksi_mulai' => today()->addDay()->toDateString(),
            'catatan' => 'Produksi untuk pesanan toko.',
            'produksiDetail' => [array_key_first($state['produksiDetail']) => [
                'bahan_baku_id' => $material->id, 'jumlah_digunakan' => 2.5,
            ]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $record = Produksi::sole();
    expect($record->status)->toBe(Produksi::DRAFT)
        ->and($record->jumlah_produksi)->toEqual(10)
        ->and((float) $record->produksiDetail()->sole()->jumlah_digunakan)->toBe(2.5)
        ->and(productionFlowStock($material))->toBe(1.0)
        ->and($product->fresh()->stok)->toEqual(4);
});

test('production dates default to the Jakarta calendar day in a UTC application', function () {
    config(['app.timezone' => 'UTC']);
    $this->travelTo(Carbon::parse('2026-10-13 01:00:00', 'UTC'));

    try {
        $product = productionFlowProduct();
        $material = productionFlowMaterial();
        $page = Livewire::test(CreateProduksi::class)
            ->assertFormSet(['produksi_mulai' => '2026-10-13']);
        $page->fillForm(fn (array $state): array => [
            'product_id' => $product->id,
            'jumlah_produksi' => 10,
            'produksiDetail' => [array_key_first($state['produksiDetail']) => [
                'bahan_baku_id' => $material->id, 'jumlah_digunakan' => 3,
            ]],
        ])->call('create')->assertHasNoFormErrors();

        $record = Produksi::sole();
        expect($record->produksi_mulai->toDateString())->toBe('2026-10-13');
        $page = Livewire::test(ViewProduksi::class, ['record' => $record->getRouteKey()]);
        $page->mountAction('startProduction')
            ->assertActionDataSet(['produksi_mulai' => '2026-10-13'])
            ->callMountedAction()->assertHasNoActionErrors();

        $page->mountAction('completeProduction')
            ->assertActionDataSet(['produksi_selesai' => '2026-10-13'])
            ->setActionData(['jumlah_hasil' => 10, 'jumlah_rusak' => 0])
            ->callMountedAction()->assertHasNoActionErrors();
        expect($record->fresh()->status)->toBe(Produksi::COMPLETED)
            ->and($record->fresh()->produksi_mulai->toDateString())->toBe('2026-10-13')
            ->and($record->fresh()->produksi_selesai->toDateString())->toBe('2026-10-13')
            ->and($product->fresh()->stok)->toEqual(10)
            ->and(productionFlowStock($material))->toBe(7.0);
    } finally {
        $this->travelBack();
    }
});

test('draft form validation can be corrected and retried without leaving partial production history', function () {
    $product = productionFlowProduct();
    $material = productionFlowMaterial();
    $page = Livewire::test(CreateProduksi::class);
    $first = array_key_first($page->get('data.produksiDetail'));
    $second = (string) Str::uuid();
    $rows = [
        $first => ['bahan_baku_id' => $material->id, 'jumlah_digunakan' => 1],
        $second => ['bahan_baku_id' => $material->id, 'jumlah_digunakan' => 2],
    ];

    $page->fillForm([
        'product_id' => $product->id, 'jumlah_produksi' => 10,
        'produksi_mulai' => today()->toDateString(), 'produksiDetail' => $rows,
    ])->call('create')->assertHasFormErrors();

    expect(Produksi::count())->toBe(0)
        ->and(ProduksiDetail::count())->toBe(0)
        ->and(productionFlowStock($material))->toBe(10.0);

    $page->set('data.produksiDetail', [$first => $rows[$first]])
        ->call('create')->assertHasNoFormErrors();
    expect(Produksi::count())->toBe(1)
        ->and(ProduksiDetail::count())->toBe(1)
        ->and(productionFlowStock($material))->toBe(10.0);
});

test('production history offers only actions appropriate to the current stage and closed records are reviewable', function () {
    $material = productionFlowMaterial();
    $draft = productionFlowRecord();
    productionFlowItem($draft, $material, 1);
    $processing = productionFlowRecord();
    productionFlowItem($processing, $material, 1);
    ProductionWorkflow::start($processing, today()->toDateString());
    $completed = productionFlowRecord();
    productionFlowItem($completed, $material, 1);
    ProductionWorkflow::start($completed, today()->toDateString());
    ProductionWorkflow::complete($completed, 8, 2, today()->toDateString());

    Livewire::test(ListProduksis::class)
        ->assertCanSeeTableRecords([$draft, $processing, $completed])
        ->assertTableActionVisible('startProduction', $draft)
        ->assertTableActionHidden('completeProduction', $draft)
        ->assertTableActionHidden('startProduction', $processing)
        ->assertTableActionVisible('completeProduction', $processing)
        ->assertTableActionHidden('completeProduction', $completed)
        ->assertTableActionHidden('cancelProduction', $completed)
        ->assertTableActionDoesNotExist('status');

    Livewire::test(ViewProduksi::class, ['record' => $completed->getRouteKey()])
        ->assertSee('Hasil layak jual')
        ->assertActionHidden('startProduction')
        ->assertActionHidden('completeProduction')
        ->assertActionHidden('cancelProduction');

    Livewire::test(ProduksiDetailRelationManager::class, ['ownerRecord' => $completed->fresh(), 'pageClass' => ViewProduksi::class])
        ->assertSee($material->nama_bahan)
        ->assertSee('Pemakaian akhir')
        ->assertTableActionHidden('create');

    expect(ProduksiResource::canEdit($draft))->toBeTrue()
        ->and(ProduksiResource::canEdit($processing))->toBeFalse()
        ->and(ProduksiResource::canEdit($completed))->toBeFalse()
        ->and(ProduksiResource::canDelete($completed))->toBeFalse()
        ->and(ProduksiResource::canDeleteAny())->toBeFalse();
    $this->get(ProduksiResource::getUrl('edit', ['record' => $completed]))->assertForbidden();
});

test('production action forms validate shortages and changed output then safely complete on retry', function () {
    $product = productionFlowProduct(4);
    $material = productionFlowMaterial(stock: 1);
    $record = productionFlowRecord($product);
    $item = productionFlowItem($record, $material, 2.5);

    $page = Livewire::test(ViewProduksi::class, ['record' => $record->getRouteKey()]);
    $page->callAction('startProduction', data: ['produksi_mulai' => today()->toDateString()])
        ->assertHasActionErrors(['produksi_mulai']);
    expect($record->fresh()->status)->toBe(Produksi::DRAFT)
        ->and(productionFlowStock($material))->toBe(1.0);

    BahanBakuDetail::where('bahan_baku_id', $material->id)->update(['stok' => 5]);
    $page->callAction('startProduction', data: ['produksi_mulai' => today()->toDateString()])
        ->assertHasNoActionErrors()
        ->assertSee('Langkah berikutnya: catat tambahan bahan')
        ->assertActionHidden('startProduction')
        ->assertActionVisible('completeProduction');
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and(productionFlowStock($material))->toBe(2.5);

    $page = Livewire::test(ViewProduksi::class, ['record' => $record->getRouteKey()]);
    $data = [
        'jumlah_hasil' => 7, 'jumlah_rusak' => 1,
        'produksi_selesai' => today()->toDateString(),
        'catatan' => null, 'returns' => [$item->id => 0.5],
    ];
    $page->callAction('completeProduction', data: $data)
        ->assertHasActionErrors(['catatan']);
    expect($record->fresh()->status)->toBe(Produksi::PROCESS)
        ->and($product->fresh()->stok)->toEqual(4)
        ->and(productionFlowStock($material))->toBe(2.5);

    $data['catatan'] = 'Dua potong masih gagal diselesaikan.';
    $page->callAction('completeProduction', data: $data)
        ->assertHasNoActionErrors()
        ->assertSee('Produksi telah selesai')
        ->assertActionHidden('completeProduction');
    expect($record->fresh()->status)->toBe(Produksi::COMPLETED)
        ->and($product->fresh()->stok)->toEqual(11)
        ->and(productionFlowStock($material))->toBe(3.0)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(0.5);
});

test('processing material action records additional issues without allowing corrections to previously taken material', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());

    $manager = Livewire::test(ProduksiDetailRelationManager::class, ['ownerRecord' => $record->fresh(), 'pageClass' => ViewProduksi::class]);
    $manager->assertTableActionVisible('create')
        ->assertTableActionDoesNotExist('edit')
        ->assertTableActionDoesNotExist('delete')
        ->callTableAction('create', data: ['bahan_baku_id' => $material->id, 'jumlah_digunakan' => 1.25])
        ->assertHasNoTableActionErrors();

    expect((float) $item->fresh()->jumlah_digunakan)->toBe(4.25)
        ->and($record->produksiDetail()->count())->toBe(1)
        ->and(productionFlowStock($material))->toBe(5.75);

    $manager->callTableAction('create', data: ['bahan_baku_id' => $material->id, 'jumlah_digunakan' => 6])
        ->assertHasTableActionErrors(['jumlah_digunakan']);
    expect((float) $item->fresh()->jumlah_digunakan)->toBe(4.25)
        ->and(productionFlowStock($material))->toBe(5.75);
});

test('material history refreshes after the parent production is completed', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());
    $manager = Livewire::test(ProduksiDetailRelationManager::class, ['ownerRecord' => $record->fresh(), 'pageClass' => ViewProduksi::class]);
    $manager->assertSee('Belum ditutup')->assertTableActionVisible('create');

    ProductionWorkflow::complete($record, 10, 0, today()->toDateString(), null, [$item->id => 1]);
    $manager->dispatch('production-updated', productionId: $record->id)
        ->assertSee('2 meter')
        ->assertDontSee('Belum ditutup')
        ->assertTableActionHidden('create');
});

test('production cancellation action requires a reason and never refunds unreported materials', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    ProductionWorkflow::start($record, today()->toDateString());
    $page = Livewire::test(ViewProduksi::class, ['record' => $record->getRouteKey()]);

    $page->callAction('cancelProduction', data: ['alasan_batal' => '', 'returns' => [$item->id => 0]])
        ->assertHasActionErrors(['alasan_batal']);
    expect($record->fresh()->status)->toBe(Produksi::PROCESS);

    $page->callAction('cancelProduction', data: [
        'alasan_batal' => 'Proses dihentikan karena mesin rusak.',
        'returns' => [$item->id => 0],
    ])->assertHasNoActionErrors();

    expect($record->fresh()->status)->toBe(Produksi::CANCELLED)
        ->and(productionFlowStock($material))->toBe(7.0)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(0.0);
});

test('a previously opened draft shows the current cancellation options after another tab starts production', function () {
    $material = productionFlowMaterial();
    $record = productionFlowRecord();
    $item = productionFlowItem($record, $material, 3);
    $page = Livewire::test(ViewProduksi::class, ['record' => $record->getRouteKey()]);

    ProductionWorkflow::start($record, today()->toDateString());
    $page->mountAction('cancelProduction')
        ->assertSee('Isi sisa bahan yang benar-benar dikembalikan')
        ->setActionData(['alasan_batal' => 'Mesin rusak.', 'returns' => [$item->id => 1]])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect($record->fresh()->status)->toBe(Produksi::CANCELLED)
        ->and(productionFlowStock($material))->toBe(8.0)
        ->and((float) $item->fresh()->jumlah_dikembalikan)->toBe(1.0);
});

test('cashiers cannot reach production pages through direct URLs', function () {
    $record = productionFlowRecord();
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(ProductionFlowPanelUser::findOrFail($cashier->id));

    expect(ProduksiResource::canViewAny())->toBeFalse()
        ->and(ProduksiResource::canCreate())->toBeFalse()
        ->and(ProduksiResource::canEdit($record))->toBeFalse();

    foreach (['index' => [], 'create' => [], 'view' => ['record' => $record], 'edit' => ['record' => $record]] as $page => $parameters) {
        $this->get(ProduksiResource::getUrl($page, $parameters))->assertForbidden();
    }
});
