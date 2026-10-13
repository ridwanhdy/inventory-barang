<?php

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\User;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\RelationManagers\PaymentsRelationManager;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class SalesPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'kasir'], true);
    }
}

function salesProduct(int $stock = 10, int $price = 30000): Product
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos']);
    $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);
    $product = Product::create([
        'nama_product' => 'Kaos pengujian '.Product::count(),
        'kategori_id' => $category, 'satuan_id' => $unit,
        'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun',
        'harga_jual' => $price, 'foto' => 'test.jpg',
    ]);
    $product->details()->firstOrFail()->update(['stok' => $stock]);

    return $product;
}

function salesOrder(): Order
{
    return Order::create([
        'nama_customer' => 'Pelanggan pengujian',
        'users_id' => User::factory()->create(['role' => 'admin'])->id,
        'tanggal_order' => '2026-10-13',
    ]);
}

function salesItem(Order $sale, Product $product, int $quantity = 1): OrderDetail
{
    return $sale->orderDetails()->create([
        'product_id' => $product->id, 'quantity' => $quantity,
        'harga' => $product->harga_jual,
    ]);
}

test('new sale stores line totals and reserves stock once with a synchronized mirror', function () {
    $product = salesProduct(10);
    $other = salesProduct(5, 20000);
    $sale = salesOrder();
    salesItem($sale, $product, 2);
    salesItem($sale, $other, 3);

    expect($sale->fresh()->subtotal)->toBe(120000)
        ->and($sale->fresh()->total_harga)->toBe(120000)
        ->and($sale->fresh()->status_pembayaran)->toBe('belum_bayar')
        ->and($product->details()->sum('stok'))->toEqual(8)
        ->and($product->fresh()->stok)->toEqual(8)
        ->and($sale->no_transaksi)->toBe('INV/2026/10/'.str_pad((string) $sale->id, 4, '0', STR_PAD_LEFT));

    $sale->update(['status_transaksi' => 'selesai']);
    $sale->refreshFinancials();
    expect($product->details()->sum('stok'))->toEqual(8)
        ->and($product->fresh()->stok)->toEqual(8);
    expect(fn () => $sale->update(['status_transaksi' => 'proses']))->toThrow(ValidationException::class);
});

test('quantity changes product changes and line removal adjust only the stock difference', function () {
    $product = salesProduct(10);
    $other = salesProduct(6, 20000);
    $sale = salesOrder();
    $item = salesItem($sale, $product, 3);

    $item->update(['quantity' => 5]);
    expect($product->details()->sum('stok'))->toEqual(5);
    $item->update(['quantity' => 2]);
    expect($product->details()->sum('stok'))->toEqual(8);
    $item->update(['product_id' => $other->id]);
    expect($product->details()->sum('stok'))->toEqual(10)
        ->and($other->details()->sum('stok'))->toEqual(4)
        ->and($item->fresh()->harga)->toBe(20000)
        ->and($sale->fresh()->total_harga)->toBe(40000);

    $item->delete();
    expect($other->details()->sum('stok'))->toEqual(6)
        ->and($other->fresh()->stok)->toEqual(6)
        ->and($sale->fresh()->total_harga)->toBe(0);
});

test('stock is checked across all stock rows and insufficient transfers roll back both products', function () {
    $product = salesProduct(2);
    ProductDetail::create(['product_id' => $product->id, 'stok' => 3]);
    $other = salesProduct(1, 25000);
    $sale = salesOrder();
    $item = salesItem($sale, $product, 4);
    expect($product->details()->sum('stok'))->toEqual(1);

    expect(fn () => $item->update(['product_id' => $other->id]))->toThrow(ValidationException::class);
    expect($item->fresh()->product_id)->toBe($product->id)
        ->and($product->details()->sum('stok'))->toEqual(1)
        ->and($other->details()->sum('stok'))->toEqual(1)
        ->and($sale->fresh()->total_harga)->toBe(120000);
});

test('invalid quantities cannot reserve stock', function ($quantity) {
    $product = salesProduct(5);
    $sale = salesOrder();
    expect(fn () => $sale->orderDetails()->create([
        'product_id' => $product->id, 'quantity' => $quantity, 'harga' => 30000,
    ]))->toThrow(ValidationException::class);
    expect($product->details()->sum('stok'))->toEqual(5)
        ->and($sale->orderDetails()->count())->toBe(0);
})->with([0, -1, 1.5]);

test('a multi-line sale failure rolls back the sale and earlier stock reservations', function () {
    $product = salesProduct(5);
    $other = salesProduct(0);
    expect(fn () => DB::transaction(function () use ($product, $other) {
        $sale = salesOrder();
        salesItem($sale, $product, 2);
        salesItem($sale, $other, 1);
    }))->toThrow(ValidationException::class);
    expect(Order::count())->toBe(0)
        ->and(OrderDetail::count())->toBe(0)
        ->and($product->details()->sum('stok'))->toEqual(5);
});

test('cancellation restores stock once keeps history and rejects further activity', function () {
    $product = salesProduct(10);
    $sale = salesOrder();
    salesItem($sale, $product, 3);
    $stale = $sale->fresh();
    $sale->cancelSale();
    $stale->cancelSale();

    expect($sale->fresh()->status_transaksi)->toBe('batal')
        ->and($sale->remainingAmount())->toBe(0.0)
        ->and($product->details()->sum('stok'))->toEqual(10)
        ->and($product->fresh()->stok)->toEqual(10)
        ->and($sale->orderDetails()->count())->toBe(1);
    expect(fn () => $sale->payments()->create(['jumlah_bayar' => 10000]))->toThrow(ValidationException::class);
    expect(fn () => $sale->delete())->toThrow(ValidationException::class);
});

test('installments use net receipts and cash change is never counted as payment', function () {
    $product = salesProduct(10, 100000);
    $sale = salesOrder();
    salesItem($sale, $product);
    $first = $sale->payments()->create(['jumlah_bayar' => 40000, 'metode_pembayaran' => 'cash']);
    expect($first->sisa_bayar)->toBe(60000)
        ->and($sale->fresh()->status_pembayaran)->toBe('cicilan');

    $last = $sale->payments()->create(['jumlah_bayar' => 80000, 'metode_pembayaran' => 'cash']);
    expect($last->kembalian)->toBe(20000)
        ->and($last->sisa_bayar)->toBe(0)
        ->and($sale->paidAmount())->toBe(100000.0)
        ->and($sale->remainingAmount())->toBe(0.0)
        ->and($sale->fresh()->status_pembayaran)->toBe('lunas');
    $sale->refreshFinancials();
    $sale->refreshFinancials();
    expect($sale->payments()->count())->toBe(2);
    expect(fn () => $sale->payments()->create(['jumlah_bayar' => 1]))->toThrow(ValidationException::class);
});

test('payment records are immutable and a paid sale cannot change items cancel or be deleted', function () {
    $product = salesProduct(10);
    $sale = salesOrder();
    $item = salesItem($sale, $product, 2);
    $payment = $sale->payments()->create(['jumlah_bayar' => 10000]);
    expect($sale->fresh()->canCancel())->toBeFalse()
        ->and($sale->fresh()->canEditItems())->toBeFalse();
    expect(fn () => $sale->cancelSale())->toThrow(ValidationException::class);
    expect(fn () => $sale->update(['status_transaksi' => 'batal']))->toThrow(ValidationException::class);
    expect(fn () => $sale->delete())->toThrow(ValidationException::class);
    expect(fn () => $item->update(['quantity' => 3]))->toThrow(ValidationException::class);
    expect(fn () => $item->delete())->toThrow(ValidationException::class);
    expect(fn () => $payment->update(['jumlah_bayar' => 20000]))->toThrow(ValidationException::class);
    expect(fn () => $payment->fresh()->delete())->toThrow(ValidationException::class);
    expect($product->details()->sum('stok'))->toEqual(8)
        ->and(Payment::count())->toBe(1);
});

test('invalid payments and excessive bank transfers are rejected without altering balances', function ($amount) {
    $product = salesProduct(10, 100000);
    $sale = salesOrder();
    salesItem($sale, $product);
    expect(fn () => $sale->payments()->create(['jumlah_bayar' => $amount, 'metode_pembayaran' => 'bank']))->toThrow(ValidationException::class);
    expect($sale->paidAmount())->toBe(0.0)
        ->and($sale->remainingAmount())->toBe(100000.0)
        ->and($sale->fresh()->status_pembayaran)->toBe('belum_bayar');
})->with([0, -1, 1.5, 100001]);

test('delivery does not imply full payment and later payments preserve stock', function () {
    $product = salesProduct(10);
    $sale = salesOrder();
    salesItem($sale, $product);
    $sale->update(['status_transaksi' => 'selesai', 'status_pembayaran' => 'lunas']);
    expect($sale->fresh()->status_pembayaran)->toBe('belum_bayar');
    $sale->payments()->create(['jumlah_bayar' => 30000]);
    expect($sale->fresh()->status_transaksi)->toBe('selesai')
        ->and($sale->fresh()->status_pembayaran)->toBe('lunas')
        ->and($product->details()->sum('stok'))->toEqual(9);
});

test('invoice shows actual sale total net payment remaining balance and change', function () {
    $product = salesProduct(10, 100000);
    $sale = salesOrder();
    salesItem($sale, $product);
    $sale->payments()->create(['jumlah_bayar' => 120000]);
    $sale->refresh();
    $html = view('pdf.invoice', [
        'order' => $sale, 'orderDetails' => $sale->orderDetails,
        'totalHarga' => 100000, 'totalBayar' => $sale->paidAmount(),
        'sisaBayar' => $sale->remainingAmount(), 'totalKembalian' => 20000,
    ])->render();
    expect($html)->toContain('BUKTI PENJUALAN', 'Pembayaran diterima:', 'Sisa pembayaran:', 'Rp100.000', 'Rp20.000', 'Hitam · Ukuran M');
});

test('cashier creates a sale with correct totals stock and an immediate payment redirect', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $product = salesProduct(10, 30000);
    $other = salesProduct(5, 20000);

    $page = Livewire::test(CreateOrder::class);
    $initialItem = array_key_first($page->get('data.orderDetails'));
    $page->fillForm([
            'nama_customer' => 'Toko pelanggan', 'tanggal_order' => '2026-10-13',
            'orderDetails' => [
                $initialItem => ['product_id' => $product->id, 'quantity' => 2, 'harga' => 30000],
                (string) Str::uuid() => ['product_id' => $other->id, 'quantity' => 3, 'harga' => 20000],
            ],
        ])->call('create')->assertHasNoFormErrors();

    $sale = Order::sole();
    expect($sale->users_id)->toBe($cashier->id)
        ->and($sale->status_transaksi)->toBe('proses')
        ->and($sale->status_pembayaran)->toBe('belum_bayar')
        ->and($sale->total_harga)->toBe(120000)
        ->and($product->details()->sum('stok'))->toEqual(8)
        ->and($other->details()->sum('stok'))->toEqual(2);
    $page->assertRedirect(OrderResource::getUrl('edit', ['record' => $sale]));
});

test('cashier sale creation rolls back every line when one product has insufficient stock', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $product = salesProduct(5);
    $unavailable = salesProduct(0);

    $page = Livewire::test(CreateOrder::class);
    $initialItem = array_key_first($page->get('data.orderDetails'));
    $page->fillForm([
        'nama_customer' => 'Toko pelanggan', 'tanggal_order' => '2026-10-13',
        'orderDetails' => [
            $initialItem => ['product_id' => $product->id, 'quantity' => 2, 'harga' => 30000],
            (string) Str::uuid() => ['product_id' => $unavailable->id, 'quantity' => 1, 'harga' => 30000],
        ],
    ])->call('create')->assertHasErrors(['orderDetails']);

    expect(Order::count())->toBe(0)
        ->and(OrderDetail::count())->toBe(0)
        ->and($product->details()->sum('stok'))->toEqual(5)
        ->and($unavailable->details()->sum('stok'))->toEqual(0);
});

test('cashier payment button opens the payment modal and records net receipts through the listener', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $sale = salesOrder();
    salesItem($sale, salesProduct(5, 100000));

    Livewire::test(EditOrder::class, ['record' => $sale->getRouteKey()])
        ->callAction('bayar')->assertDispatched('open-sale-payment', orderId: $sale->id);

    $manager = Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $sale->fresh(), 'pageClass' => EditOrder::class]);
    $manager->dispatch('open-sale-payment', orderId: $sale->id + 1)->assertTableActionNotMounted('create');
    $manager->dispatch('open-sale-payment', orderId: $sale->id)
        ->assertTableActionMounted('create')
        ->setTableActionData(['jumlah_bayar' => 120000, 'metode_pembayaran' => 'cash'])
        ->callMountedTableAction()->assertHasNoTableActionErrors()
        ->assertDispatched('sale-payment-recorded', orderId: $sale->id);

    expect($sale->paidAmount())->toBe(100000.0)
        ->and($sale->payments()->sole()->kembalian)->toBe(20000)
        ->and($sale->fresh()->status_pembayaran)->toBe('lunas');
    $manager->dispatch('open-sale-payment', orderId: $sale->id)->assertTableActionNotMounted('create');
});

test('sale receipts require login before loading any sale', function () {
    $this->get('/admin/order/999/print-struk')
        ->assertRedirect(route('filament.admin.auth.login'));
});

test('payment button saves pending quantity changes before opening the current balance', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $product = salesProduct(10, 30000);
    $sale = salesOrder();
    salesItem($sale, $product);

    $page = Livewire::test(EditOrder::class, ['record' => $sale->getRouteKey()]);
    $itemKey = array_key_first($page->get('data.orderDetails'));
    $page->fillForm(['orderDetails' => [
        $itemKey => ['product_id' => $product->id, 'quantity' => 3, 'harga' => 30000],
    ]])->callAction('bayar')->assertHasNoFormErrors()
        ->assertDispatched('open-sale-payment', orderId: $sale->id);

    expect($sale->fresh()->total_harga)->toBe(90000)
        ->and($sale->remainingAmount())->toBe(90000.0)
        ->and($product->details()->sum('stok'))->toEqual(7);

    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $sale->fresh(), 'pageClass' => EditOrder::class])
        ->dispatch('open-sale-payment', orderId: $sale->id)
        ->assertTableActionMounted('create')
        ->setTableActionData(['jumlah_bayar' => 90000, 'metode_pembayaran' => 'cash'])
        ->callMountedTableAction()->assertHasNoTableActionErrors();
    expect($sale->paidAmount())->toBe(90000.0)
        ->and($sale->fresh()->status_pembayaran)->toBe('lunas')
        ->and($product->details()->sum('stok'))->toEqual(7);
});

test('delivery button saves pending item changes and never debits the stock again', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $product = salesProduct(10, 30000);
    $sale = salesOrder();
    salesItem($sale, $product);

    $page = Livewire::test(EditOrder::class, ['record' => $sale->getRouteKey()]);
    $itemKey = array_key_first($page->get('data.orderDetails'));
    $page->fillForm(['orderDetails' => [
        $itemKey => ['product_id' => $product->id, 'quantity' => 3, 'harga' => 30000],
    ]])->callAction('serahkan_barang')->assertHasNoFormErrors();

    expect($sale->fresh()->status_transaksi)->toBe('selesai')
        ->and($sale->fresh()->status_pembayaran)->toBe('belum_bayar')
        ->and($sale->fresh()->total_harga)->toBe(90000)
        ->and($product->details()->sum('stok'))->toEqual(7)
        ->and($product->fresh()->stok)->toEqual(7);
});

test('cashier can correct and retry a failed sale on the same form without cached partial lines', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $product = salesProduct(5, 30000);
    $other = salesProduct(1, 20000);
    $page = Livewire::test(CreateOrder::class);
    $firstKey = array_key_first($page->get('data.orderDetails'));
    $secondKey = (string) Str::uuid();
    $rows = [
        $firstKey => ['product_id' => $product->id, 'quantity' => 2, 'harga' => 30000],
        $secondKey => ['product_id' => $other->id, 'quantity' => 2, 'harga' => 20000],
    ];

    $page->fillForm([
        'nama_customer' => 'Toko pelanggan', 'tanggal_order' => '2026-10-13', 'orderDetails' => $rows,
    ])->call('create')->assertHasErrors(['orderDetails']);
    expect(Order::count())->toBe(0)
        ->and(OrderDetail::count())->toBe(0)
        ->and($product->details()->sum('stok'))->toEqual(5)
        ->and($other->details()->sum('stok'))->toEqual(1);

    $rows[$secondKey]['quantity'] = 1;
    $page->fillForm(['orderDetails' => $rows])->call('create')->assertHasNoFormErrors();
    expect(Order::count())->toBe(1)
        ->and(OrderDetail::count())->toBe(2)
        ->and(Order::sole()->total_harga)->toBe(80000)
        ->and($product->details()->sum('stok'))->toEqual(3)
        ->and($product->fresh()->stok)->toEqual(3)
        ->and($other->details()->sum('stok'))->toEqual(0)
        ->and(Payment::count())->toBe(0);
});

test('payment history offers no second button that could bypass unsaved sale corrections', function () {
    $cashier = User::factory()->create(['role' => 'kasir']);
    $this->actingAs(SalesPanelUser::findOrFail($cashier->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $sale = salesOrder();
    salesItem($sale, salesProduct(5, 30000));

    Livewire::test(PaymentsRelationManager::class, ['ownerRecord' => $sale->fresh(), 'pageClass' => EditOrder::class])
        ->assertSee('Riwayat pembayaran')
        ->assertSee('Gunakan tombol Bayar')
        ->assertDontSee('Catat pembayaran')
        ->assertTableActionExists('create')
        ->dispatch('open-sale-payment', orderId: $sale->id)
        ->assertTableActionMounted('create');
});
