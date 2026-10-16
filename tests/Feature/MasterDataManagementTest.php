<?php

use App\Filament\Resources\BahanBakuDetailResource;
use App\Filament\Resources\BahanBakuResource\Pages\CreateBahanBaku;
use App\Filament\Resources\BahanBakuResource\Pages\EditBahanBaku;
use App\Filament\Resources\BahanBakuResource\Pages\ListBahanBakus;
use App\Filament\Resources\BahanBakuResource\Pages\ViewBahanBaku;
use App\Filament\Resources\KategoriResource\Pages\CreateKategori;
use App\Filament\Resources\KategoriResource\Pages\EditKategori;
use App\Filament\Resources\KategoriResource\Pages\ListKategoris;
use App\Filament\Resources\PemasokResource\Pages\CreatePemasok;
use App\Filament\Resources\PemasokResource\Pages\EditPemasok;
use App\Filament\Resources\PemasokResource\Pages\ListPemasoks;
use App\Filament\Resources\PemasokResource\Pages\ViewPemasok;
use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PembelianResource\Pages\CreatePembelian;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProduksiResource\Pages\CreateProduksi;
use App\Filament\Resources\SatuanResource\Pages\CreateSatuan;
use App\Filament\Resources\SatuanResource\Pages\EditSatuan;
use App\Filament\Resources\SatuanResource\Pages\ListSatuans;
use App\Models\BahanBaku;
use App\Models\Kategori;
use App\Models\Pemasok;
use App\Models\Pembelian;
use App\Models\Product;
use App\Models\Produksi;
use App\Models\Satuan;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Storage::fake('public');
});

function masterManagementProduct(array $attributes = []): Product
{
    return Product::create(array_replace([
        'nama_product' => 'Kaos pengujian',
        'kategori_id' => $attributes['kategori_id'] ?? Kategori::create(['nama_kategori' => 'Pakaian'])->id,
        'satuan_id' => $attributes['satuan_id'] ?? Satuan::create(['nama_satuan' => 'pcs'])->id,
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'harga_jual' => 50000,
        'foto' => '',
    ], $attributes));
}

function masterManagementMaterial(array $attributes = []): BahanBaku
{
    return BahanBaku::create(array_replace([
        'nama_bahan' => 'Kain pengujian',
        'kategori_id' => $attributes['kategori_id'] ?? Kategori::create(['nama_kategori' => 'Tekstil'])->id,
        'satuan_id' => $attributes['satuan_id'] ?? Satuan::create(['nama_satuan' => 'meter'])->id,
        'jenis' => 'bahan baku',
    ], $attributes));
}

dataset('master management lookups', [
    'kategori' => [Kategori::class, 'nama_kategori', 'kategori_id', CreateKategori::class, EditKategori::class, ListKategoris::class],
    'satuan' => [Satuan::class, 'nama_satuan', 'satuan_id', CreateSatuan::class, EditSatuan::class, ListSatuans::class],
]);

test('lookup forms normalize names and reject duplicates when creating', function (string $model, string $field, string $foreignKey, string $createPage) {
    $existing = $model::create([$field => 'Nama terpakai']);

    Livewire::test($createPage)
        ->fillForm([$field => '  Nama terpakai  '])
        ->call('create')
        ->assertHasFormErrors([$field => 'unique']);

    expect($model::count())->toBe(1);

    Livewire::test($createPage)
        ->fillForm([$field => '  Nama baru  '])
        ->call('create')
        ->assertHasNoFormErrors();

    expect($model::where($field, 'Nama baru')->exists())->toBeTrue()
        ->and($existing->fresh()->getAttribute($field))->toBe('Nama terpakai');
})->with('master management lookups');

test('lookup edits allow their own name but reject another record name', function (string $model, string $field, string $foreignKey, string $createPage, string $editPage) {
    $existing = $model::create([$field => 'Nama terpakai']);
    $other = $model::create([$field => 'Nama lainnya']);

    Livewire::test($editPage, ['record' => $other->getRouteKey()])
        ->fillForm([$field => '  Nama lainnya  '])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($other->fresh()->getAttribute($field))->toBe('Nama lainnya');

    Livewire::test($editPage, ['record' => $other->getRouteKey()])
        ->fillForm([$field => '  Nama terpakai  '])
        ->call('save')
        ->assertHasFormErrors([$field => 'unique']);

    expect($other->fresh()->getAttribute($field))->toBe('Nama lainnya')
        ->and($existing->fresh()->getAttribute($field))->toBe('Nama terpakai');
})->with('master management lookups');

test('lookup lists search names and show usage across products and materials', function (string $model, string $field, string $foreignKey, string $createPage, string $editPage, string $listPage) {
    $unused = $model::create([$field => 'Belum dipakai']);
    $productOnly = $model::create([$field => 'Khusus produk']);
    $materialOnly = $model::create([$field => 'Khusus bahan']);
    $combined = $model::create([$field => 'Gabungan master']);

    masterManagementProduct([$foreignKey => $productOnly->id]);
    masterManagementMaterial([$foreignKey => $materialOnly->id]);
    masterManagementProduct([$foreignKey => $combined->id]);
    masterManagementProduct([$foreignKey => $combined->id]);
    masterManagementMaterial([$foreignKey => $combined->id]);

    Livewire::test($listPage)
        ->assertCanSeeTableRecords([$unused, $productOnly, $materialOnly, $combined])
        ->assertTableColumnStateSet('product_count', 2, $combined->id)
        ->assertTableColumnStateSet('bahan_bakus_count', 1, $combined->id)
        ->searchTable('Gabungan')
        ->assertCanSeeTableRecords([$combined])
        ->assertCanNotSeeTableRecords([$unused, $productOnly, $materialOnly])
        ->searchTable('')
        ->filterTable('digunakan', true)
        ->assertCanSeeTableRecords([$productOnly, $materialOnly, $combined])
        ->assertCanNotSeeTableRecords([$unused])
        ->filterTable('digunakan', false)
        ->assertCanSeeTableRecords([$unused])
        ->assertCanNotSeeTableRecords([$productOnly, $materialOnly, $combined]);
})->with('master management lookups');

test('product forms reject negative and fractional prices without changing saved products', function (int|float $price, string $rule) {
    $product = masterManagementProduct();
    $data = $product->only(['nama_product', 'kategori_id', 'satuan_id', 'ukuran', 'warna', 'bahan', 'harga_jual']);

    Livewire::test(CreateProduct::class)
        ->fillForm(array_replace($data, ['harga_jual' => $price]))
        ->call('create')
        ->assertHasFormErrors(['harga_jual' => $rule]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm(['harga_jual' => $price])
        ->call('save')
        ->assertHasFormErrors(['harga_jual' => $rule]);

    expect(Product::count())->toBe(1)
        ->and($product->fresh()->harga_jual)->toBe(50000);
})->with([
    'negative' => [-100, 'min'],
    'fractional' => [100.5, 'integer'],
]);

test('product form accepts zero prices and retains separate variants of the same name', function () {
    $product = masterManagementProduct();
    $data = $product->only(['nama_product', 'kategori_id', 'satuan_id', 'ukuran', 'warna', 'bahan', 'harga_jual']);

    Livewire::test(CreateProduct::class)
        ->fillForm(array_replace($data, ['ukuran' => '  XL  ', 'warna' => '  Biru  ', 'harga_jual' => 0]))
        ->call('create')
        ->assertHasNoFormErrors();

    $variant = Product::whereKeyNot($product->id)->sole();

    expect($variant->nama_product)->toBe($product->nama_product)
        ->and($variant->ukuran)->toBe('XL')
        ->and($variant->warna)->toBe('Biru')
        ->and($variant->harga_jual)->toBe(0);
});

test('product list finds variants and related category and unit names', function (string $query) {
    $category = Kategori::create(['nama_kategori' => 'Kategori unggulan']);
    $unit = Satuan::create(['nama_satuan' => 'pak spesial']);
    $target = masterManagementProduct([
        'kategori_id' => $category->id,
        'satuan_id' => $unit->id,
        'ukuran' => 'XXL',
        'warna' => 'Marun',
        'bahan' => 'Linen',
    ]);
    $other = masterManagementProduct();

    Livewire::test(ListProducts::class)
        ->searchTable($query)
        ->assertCanSeeTableRecords([$target])
        ->assertCanNotSeeTableRecords([$other]);
})->with(['XXL', 'Marun', 'Linen', 'Kategori unggulan', 'pak spesial']);

test('product filters combine category and variants without mixing records', function () {
    $target = masterManagementProduct(['ukuran' => 'L', 'warna' => 'Biru', 'bahan' => 'Linen']);
    $otherVariant = masterManagementProduct(['kategori_id' => $target->kategori_id, 'ukuran' => 'S', 'warna' => 'Biru', 'bahan' => 'Linen']);
    $otherCategory = masterManagementProduct(['ukuran' => 'L', 'warna' => 'Biru', 'bahan' => 'Linen']);
    $otherColor = masterManagementProduct(['kategori_id' => $target->kategori_id, 'ukuran' => 'L', 'warna' => 'Putih', 'bahan' => 'Linen']);

    Livewire::test(ListProducts::class)
        ->filterTable('kategori_id', $target->kategori_id)
        ->filterTable('ukuran', 'L')
        ->filterTable('warna', 'Biru')
        ->filterTable('bahan', 'Linen')
        ->assertCanSeeTableRecords([$target])
        ->assertCanNotSeeTableRecords([$otherVariant, $otherCategory, $otherColor]);
});

test('material filters combine category and unit and support related-name search', function () {
    $category = Kategori::create(['nama_kategori' => 'Kategori tekstil utama']);
    $target = masterManagementMaterial(['kategori_id' => $category->id]);
    $otherUnit = masterManagementMaterial(['kategori_id' => $category->id]);
    $otherCategory = masterManagementMaterial(['satuan_id' => $target->satuan_id]);

    Livewire::test(ListBahanBakus::class)
        ->searchTable($category->nama_kategori)
        ->assertCanSeeTableRecords([$target, $otherUnit])
        ->assertCanNotSeeTableRecords([$otherCategory])
        ->searchTable('')
        ->filterTable('kategori_id', $category->id)
        ->filterTable('satuan_id', $target->satuan_id)
        ->assertCanSeeTableRecords([$target])
        ->assertCanNotSeeTableRecords([$otherUnit, $otherCategory]);
});

test('material creation without a type starts at zero and supports receipts and production planning', function () {
    $category = Kategori::create(['nama_kategori' => 'Kain']);
    $unit = Satuan::create(['nama_satuan' => 'meter']);

    Livewire::test(CreateBahanBaku::class)
        ->fillForm([
            'nama_bahan' => '  Kain untuk produksi  ',
            'kategori_id' => $category->id,
            'satuan_id' => $unit->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $material = BahanBaku::sole();

    expect($material->nama_bahan)->toBe('Kain untuk produksi')
        ->and($material->jenis)->toBe('bahan baku')
        ->and($material->details()->count())->toBe(1)
        ->and((float) $material->details()->sum('stok'))->toBe(0.0);
    $this->assertDatabaseCount('bahan_baku_histories', 0);
    $this->assertDatabaseCount('products', 0);

    $supplier = Pemasok::create(['nama_pemasok' => 'Pemasok kain', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan']);

    Livewire::test(CreatePembelian::class)
        ->fillForm(fn (array $state): array => [
            'pemasok_id' => $supplier->id,
            'tanggal_pembelian' => today()->toDateString(),
            'pembelianDetails' => [array_key_first($state['pembelianDetails']) => [
                'bahan_baku_id' => $material->id, 'quantity' => 12.5,
            ]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Pembelian::sole()->pembelianDetails()->sole()->bahan_baku_id)->toBe($material->id)
        ->and((float) $material->details()->sum('stok'))->toBe(12.5);

    $product = masterManagementProduct();

    Livewire::test(CreateProduksi::class)
        ->fillForm(fn (array $state): array => [
            'product_id' => $product->id,
            'jumlah_produksi' => 10,
            'produksi_mulai' => today('Asia/Jakarta')->toDateString(),
            'produksiDetail' => [array_key_first($state['produksiDetail']) => [
                'bahan_baku_id' => $material->id, 'jumlah_digunakan' => 2.5,
            ]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $production = Produksi::sole();

    expect($production->status)->toBe(Produksi::DRAFT)
        ->and($production->produksiDetail()->sole()->bahan_baku_id)->toBe($material->id)
        ->and((float) $material->details()->sum('stok'))->toBe(12.5);
});

test('editing material identity preserves existing classification and stock', function (string $type) {
    $material = masterManagementMaterial(['jenis' => $type]);
    DB::table('bahan_baku_details')->where('bahan_baku_id', $material->id)->update(['stok' => 17.5]);
    $stockBefore = DB::table('bahan_baku_details')->where('bahan_baku_id', $material->id)->get()->toArray();
    $category = Kategori::create(['nama_kategori' => 'Kategori diperbarui']);

    Livewire::test(EditBahanBaku::class, ['record' => $material->getRouteKey()])
        ->fillForm(['nama_bahan' => '  Nama bahan diperbarui  ', 'kategori_id' => $category->id])
        ->call('save')
        ->assertHasNoFormErrors();

    $material->refresh();

    expect($material->nama_bahan)->toBe('Nama bahan diperbarui')
        ->and($material->kategori_id)->toBe($category->id)
        ->and($material->jenis)->toBe($type)
        ->and(BahanBaku::count())->toBe(1)
        ->and(DB::table('bahan_baku_details')->where('bahan_baku_id', $material->id)->get()->toArray())->toEqual($stockBefore);
    $this->assertDatabaseCount('bahan_baku_histories', 0);
})->with(['bahan baku', 'bahan jadi']);

test('supplier forms save normalized contact details when creating and editing', function () {
    Livewire::test(CreatePemasok::class)
        ->fillForm([
            'nama_pemasok' => '  CV Sumber Kain  ',
            'nomor_telepon' => '  +62 812 3456 7890  ',
            'alamat' => '  Jalan Merdeka 12, Magetan  ',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $supplier = Pemasok::sole();
    expect($supplier->nama_pemasok)->toBe('CV Sumber Kain')
        ->and($supplier->nomor_telepon)->toBe('+62 812 3456 7890')
        ->and($supplier->alamat)->toBe('Jalan Merdeka 12, Magetan');

    Livewire::test(EditPemasok::class, ['record' => $supplier->getRouteKey()])
        ->fillForm([
            'nama_pemasok' => '  CV Sumber Tekstil  ',
            'nomor_telepon' => '  (0351) 123-456  ',
            'alamat' => '  Jalan Melati 9, Madiun  ',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $supplier->refresh();
    expect($supplier->nama_pemasok)->toBe('CV Sumber Tekstil')
        ->and($supplier->nomor_telepon)->toBe('(0351) 123-456')
        ->and($supplier->alamat)->toBe('Jalan Melati 9, Madiun');

    Livewire::test(ViewPemasok::class, ['record' => $supplier->getRouteKey()])
        ->assertOk()
        ->assertSee('Belum ada penerimaan');
});

test('supplier forms reject blank identities and invalid telephone text', function () {
    $invalid = ['nama_pemasok' => '   ', 'nomor_telepon' => 'hubungi nanti', 'alamat' => '   '];
    $errors = ['nama_pemasok' => 'required', 'nomor_telepon' => 'regex', 'alamat' => 'required'];

    Livewire::test(CreatePemasok::class)
        ->fillForm($invalid)
        ->call('create')
        ->assertHasFormErrors($errors);

    $this->assertDatabaseCount('pemasoks', 0);
    $supplier = Pemasok::create(['nama_pemasok' => 'Pemasok valid', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan']);

    Livewire::test(EditPemasok::class, ['record' => $supplier->getRouteKey()])
        ->fillForm($invalid)
        ->call('save')
        ->assertHasFormErrors($errors);

    expect($supplier->fresh()->only(['nama_pemasok', 'nomor_telepon', 'alamat']))->toBe([
        'nama_pemasok' => 'Pemasok valid', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan',
    ]);
});

test('supplier list counts receipts and distinguishes used suppliers while protecting their history', function () {
    $used = Pemasok::create(['nama_pemasok' => 'Pemasok aktif', 'nomor_telepon' => '081234567890', 'alamat' => 'Magetan']);
    $unused = Pemasok::create(['nama_pemasok' => 'Pemasok baru', 'nomor_telepon' => '081234567891', 'alamat' => 'Madiun']);

    foreach (['2026-10-01', '2026-10-02'] as $date) {
        DB::table('pembelians')->insert(['pemasok_id' => $used->id, 'tanggal_pembelian' => $date]);
    }

    Livewire::test(ListPemasoks::class)
        ->assertTableColumnStateSet('pembelians_count', 2, $used->id)
        ->assertTableColumnStateSet('pembelians_count', 0, $unused->id)
        ->assertTableActionHidden('delete', $used)
        ->assertTableActionVisible('delete', $unused)
        ->filterTable('digunakan', true)
        ->assertCanSeeTableRecords([$used])
        ->assertCanNotSeeTableRecords([$unused])
        ->filterTable('digunakan', false)
        ->assertCanSeeTableRecords([$unused])
        ->assertCanNotSeeTableRecords([$used])
        ->callTableAction('delete', $unused);

    $this->assertDatabaseMissing('pemasoks', ['id' => $unused->id]);
    $this->assertDatabaseHas('pemasoks', ['id' => $used->id]);
    $this->assertDatabaseCount('pembelians', 2);

    Livewire::test(ViewPemasok::class, ['record' => $used->getRouteKey()])
        ->assertOk()
        ->assertSee('02 Okt 2026')
        ->assertActionHasUrl('riwayat_penerimaan', PembelianResource::getUrl('index', [
            'tableFilters' => ['pemasok' => ['value' => $used->id]],
        ]));
});

test('material detail renders without offering direct stock changes', function () {
    $material = masterManagementMaterial();
    DB::table('bahan_baku_details')->insert([
        ['bahan_baku_id' => $material->id, 'stok' => 12.5],
        ['bahan_baku_id' => $material->id, 'stok' => 7.5],
    ]);

    Livewire::test(ViewBahanBaku::class, ['record' => $material->getRouteKey()])
        ->assertOk()
        ->assertSee($material->nama_bahan)
        ->assertSee('20 meter')
        ->assertActionHasUrl('lihat_stok', BahanBakuDetailResource::getUrl('index', [
            'tableFilters' => ['bahanBaku' => ['value' => $material->id]],
        ]))
        ->assertActionHasUrl('catat_penerimaan', PembelianResource::getUrl('create'))
        ->assertActionDoesNotExist('increaseStock');

    expect((float) DB::table('bahan_baku_details')->where('bahan_baku_id', $material->id)->sum('stok'))->toBe(20.0);
    $this->assertDatabaseCount('bahan_baku_histories', 0);
});
