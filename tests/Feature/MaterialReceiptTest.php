<?php

use App\Filament\Resources\PembelianResource;
use App\Filament\Resources\PembelianResource\Pages\CreatePembelian;
use App\Filament\Resources\PembelianResource\Pages\EditPembelian;
use App\Filament\Resources\PembelianResource\Pages\ListPembelians;
use App\Filament\Resources\PembelianResource\Pages\ViewPembelian;
use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;
use App\Models\Pemasok;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class MaterialReceiptPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'kasir'], true);
    }
}

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs(MaterialReceiptPanelUser::findOrFail($user->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::withoutLazyLoading();

    $this->supplier = Pemasok::create([
        'nama_pemasok' => 'Pemasok bahan pengujian',
        'nomor_telepon' => '0800000000',
        'alamat' => 'Alamat pengujian',
    ]);
});

function receiptMaterial(string $name, string $unit, float $stock = 0): BahanBaku
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Bahan baku']);
    $unitId = DB::table('satuans')->insertGetId(['nama_satuan' => $unit]);
    $materialId = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => $name,
        'kategori_id' => $category,
        'satuan_id' => $unitId,
        'jenis' => 'bahan baku',
    ]);
    BahanBakuDetail::create(['bahan_baku_id' => $materialId, 'stok' => $stock]);

    return BahanBaku::findOrFail($materialId);
}

function receiptRecord(Pemasok $supplier): Pembelian
{
    return Pembelian::create([
        'pemasok_id' => $supplier->id,
        'tanggal_pembelian' => today(),
        'catatan' => 'Penerimaan pengujian',
    ]);
}

function receiptStock(BahanBaku $material): float
{
    return (float) BahanBakuDetail::where('bahan_baku_id', $material->id)->firstOrFail()->stok;
}

test('material receipt preserves fractional quantities and adjusts stock by correction delta', function () {
    $material = receiptMaterial('Kain katun', 'meter', 2);
    $receipt = receiptRecord($this->supplier);
    $detail = $receipt->pembelianDetails()->create(['bahan_baku_id' => $material->id, 'quantity' => 0.5]);

    expect($detail->fresh()->quantity)->toBe('0.50')
        ->and(receiptStock($material))->toBe(2.5);

    $detail->update(['quantity' => 1.75]);
    expect(receiptStock($material))->toBe(3.75);

    $detail->update(['quantity' => 0.25]);
    expect(receiptStock($material))->toBe(2.25);
});

test('changing received material restores the old material and adds to the new one', function () {
    $old = receiptMaterial('Kain lama', 'meter', 15);
    $new = receiptMaterial('Kain pengganti', 'kg', 5);
    $detail = receiptRecord($this->supplier)->pembelianDetails()->create(['bahan_baku_id' => $old->id, 'quantity' => 10]);

    $detail->update(['bahan_baku_id' => $new->id, 'quantity' => 8]);

    expect(receiptStock($old))->toBe(15.0)
        ->and(receiptStock($new))->toBe(13.0);
});

test('unsafe correction is rolled back after the received material has been used', function () {
    $material = receiptMaterial('Kain sudah dipakai', 'meter');
    $detail = receiptRecord($this->supplier)->pembelianDetails()->create(['bahan_baku_id' => $material->id, 'quantity' => 10]);
    BahanBakuDetail::where('bahan_baku_id', $material->id)->update(['stok' => 3]);

    expect(fn () => $detail->update(['quantity' => 1]))->toThrow(ValidationException::class);

    expect($detail->fresh()->quantity)->toBe('10.00')
        ->and(receiptStock($material))->toBe(3.0);
});

test('failed detail insertion rolls back its stock adjustment', function () {
    $material = receiptMaterial('Bahan rollback', 'kg', 2);

    expect(fn () => PembelianDetail::create([
        'pembelian_id' => 999999,
        'bahan_baku_id' => $material->id,
        'quantity' => 0.5,
    ]))->toThrow(QueryException::class);

    expect(receiptStock($material))->toBe(2.0)
        ->and(PembelianDetail::count())->toBe(0);
});

test('invalid receipt quantities never change stock', function ($quantity) {
    $material = receiptMaterial('Bahan validasi', 'kg', 2);
    $receipt = receiptRecord($this->supplier);

    expect(fn () => $receipt->pembelianDetails()->create(['bahan_baku_id' => $material->id, 'quantity' => $quantity]))
        ->toThrow(ValidationException::class);

    expect(receiptStock($material))->toBe(2.0)
        ->and($receipt->pembelianDetails()->count())->toBe(0);
})->with([0, -1, 0.001]);

test('deleting a receipt through its model reverses its details and rolls back an unsafe deletion', function () {
    $first = receiptMaterial('Kain pertama', 'meter');
    $second = receiptMaterial('Benang kedua', 'kg');
    $receipt = receiptRecord($this->supplier);
    $receipt->pembelianDetails()->create(['bahan_baku_id' => $first->id, 'quantity' => 10]);
    $receipt->pembelianDetails()->create(['bahan_baku_id' => $second->id, 'quantity' => 4]);
    BahanBakuDetail::where('bahan_baku_id', $second->id)->update(['stok' => 1]);

    expect(fn () => $receipt->delete())->toThrow(ValidationException::class);

    expect(receiptStock($first))->toBe(10.0)
        ->and(receiptStock($second))->toBe(1.0)
        ->and($receipt->fresh()->pembelianDetails()->count())->toBe(2);

    BahanBakuDetail::where('bahan_baku_id', $second->id)->update(['stok' => 4]);
    $receipt->delete();

    expect(receiptStock($first))->toBe(0.0)
        ->and(receiptStock($second))->toBe(0.0)
        ->and(PembelianDetail::count())->toBe(0);
});

test('receipt form records fractional material and edit hydrates its unit', function () {
    $material = receiptMaterial('Kain untuk form', 'meter', 2);

    Livewire::test(CreatePembelian::class)
        ->fillForm(fn (array $state): array => [
            'pemasok_id' => $this->supplier->id,
            'tanggal_pembelian' => today()->toDateString(),
            'catatan' => 'Bahan benar-benar diterima',
            'pembelianDetails' => [array_key_first($state['pembelianDetails']) => ['bahan_baku_id' => $material->id, 'quantity' => 0.5]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $receipt = Pembelian::latest('id')->firstOrFail();
    expect(receiptStock($material))->toBe(2.5);

    $page = Livewire::test(EditPembelian::class, ['record' => $receipt->getRouteKey()]);
    $details = $page->get('data.pembelianDetails');
    expect(array_values($details)[0]['satuan'])->toBe('meter');

    $page->assertSee('Koreksi Penerimaan Bahan Baku')->assertSee('Simpan koreksi');
});

test('receipt history displays each unit and has no misleading monetary totals', function () {
    $cloth = receiptMaterial('Kain daftar', 'meter');
    $thread = receiptMaterial('Benang daftar', 'kg');
    $receipt = receiptRecord($this->supplier);
    $receipt->pembelianDetails()->create(['bahan_baku_id' => $cloth->id, 'quantity' => 10]);
    $receipt->pembelianDetails()->create(['bahan_baku_id' => $thread->id, 'quantity' => 0.5]);

    Livewire::test(ListPembelians::class)
        ->assertSee('Penerimaan Bahan Baku')
        ->assertSee('10 meter')
        ->assertSee('0,5 kg')
        ->assertDontSee('Total Quantity')
        ->searchTable('Benang daftar')
        ->assertCanSeeTableRecords([$receipt]);

    Livewire::test(ViewPembelian::class, ['record' => $receipt->getRouteKey()])
        ->assertSee('0,5 kg')
        ->assertDontSee('Harga Satuan')
        ->assertDontSee('Total Harga');

    expect(PembelianResource::canDelete($receipt))->toBeFalse()
        ->and(PembelianResource::canDeleteAny())->toBeFalse();
});

test('receipt form can retry after a relationship failure without creating stock twice', function () {
    $first = receiptMaterial('Kain retry', 'meter', 2);
    $second = receiptMaterial('Benang retry', 'kg', 999999);

    $page = Livewire::test(CreatePembelian::class)
        ->fillForm(fn (array $state): array => [
            'pemasok_id' => $this->supplier->id,
            'tanggal_pembelian' => today()->toDateString(),
            'catatan' => 'Input dipertahankan setelah validasi',
            'pembelianDetails' => [
                array_key_first($state['pembelianDetails']) => ['bahan_baku_id' => $first->id, 'quantity' => 0.5],
                'additional-material' => ['bahan_baku_id' => $second->id, 'quantity' => 2],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['pembelianDetails']);

    expect(Pembelian::count())->toBe(0)
        ->and(PembelianDetail::count())->toBe(0)
        ->and(receiptStock($first))->toBe(2.0)
        ->and(receiptStock($second))->toBe(999999.0)
        ->and($page->get('data.catatan'))->toBe('Input dipertahankan setelah validasi')
        ->and($page->get('record'))->toBeNull();

    $page->fillForm(fn (array $state): array => [
        'pembelianDetails' => array_map(
            fn (array $detail): array => array_replace($detail, [
                'quantity' => (int) $detail['bahan_baku_id'] === $second->id ? 0.5 : $detail['quantity'],
            ]),
            $state['pembelianDetails'],
        ),
    ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Pembelian::count())->toBe(1)
        ->and(PembelianDetail::count())->toBe(2)
        ->and(receiptStock($first))->toBe(2.5)
        ->and(receiptStock($second))->toBe(999999.5);
});

test('unsafe correction shows an actionable form error without changing receipt history', function () {
    $material = receiptMaterial('Kain koreksi form', 'meter');
    $receipt = receiptRecord($this->supplier);
    $detail = $receipt->pembelianDetails()->create(['bahan_baku_id' => $material->id, 'quantity' => 10]);
    BahanBakuDetail::where('bahan_baku_id', $material->id)->update(['stok' => 3]);

    Livewire::test(EditPembelian::class, ['record' => $receipt->getRouteKey()])
        ->fillForm(fn (array $state): array => [
            'pembelianDetails' => [array_key_first($state['pembelianDetails']) => ['bahan_baku_id' => $material->id, 'quantity' => 1]],
        ])
        ->call('save')
        ->assertHasFormErrors(['pembelianDetails'])
        ->assertSee('stok akan menjadi negatif');

    expect($detail->fresh()->quantity)->toBe('10.00')
        ->and(receiptStock($material))->toBe(3.0);
});

test('cashiers cannot reach material receipt pages by direct URL', function () {
    $receipt = receiptRecord($this->supplier);
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(MaterialReceiptPanelUser::findOrFail($cashier->id));

    expect(PembelianResource::canViewAny())->toBeFalse()
        ->and(PembelianResource::canCreate())->toBeFalse()
        ->and(PembelianResource::canEdit($receipt))->toBeFalse();

    foreach (['index' => [], 'create' => [], 'view' => ['record' => $receipt], 'edit' => ['record' => $receipt]] as $page => $parameters) {
        $this->get(PembelianResource::getUrl($page, $parameters))->assertForbidden();
    }
});
