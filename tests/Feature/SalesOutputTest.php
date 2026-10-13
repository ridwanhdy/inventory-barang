<?php

use App\Filament\Resources\LaporanPenjualanResource\Pages\ListLaporanPenjualan;
use App\Filament\Resources\OrderResource\Pages\PrintInvoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class SalesOutputPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'kasir'], true);
    }
}

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs(SalesOutputPanelUser::findOrFail($user->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function salesOutputSale(string $customer = 'Sinta pelanggan'): Order
{
    $product = Product::create([
        'nama_product' => 'Kaos cetak ' . Product::count(),
        'kategori_id' => DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kaos']),
        'satuan_id' => DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']),
        'ukuran' => 'M',
        'warna' => 'Hitam',
        'bahan' => 'Katun',
        'harga_jual' => 30000,
        'foto' => '',
    ]);
    $product->details()->firstOrFail()->update(['stok' => 5]);

    $sale = Order::create([
        'nama_customer' => $customer,
        'users_id' => auth()->id(),
        'tanggal_order' => '2026-10-13',
    ]);
    $sale->orderDetails()->create(['product_id' => $product->id, 'quantity' => 3]);
    $sale->payments()->create(['jumlah_bayar' => 20000, 'metode_pembayaran' => 'cash']);
    $sale->payments()->create(['jumlah_bayar' => 100000, 'metode_pembayaran' => 'cash']);
    $sale->update(['status_transaksi' => 'selesai']);

    return $sale->refresh();
}

test('sales report displays and searches the stored customer name and sale number', function () {
    $sale = salesOutputSale();
    $other = salesOutputSale('Rahma pelanggan');

    Livewire::test(ListLaporanPenjualan::class)
        ->assertCanSeeTableRecords([$sale, $other])
        ->assertSee('Nomor penjualan')
        ->assertSee('Pelanggan')
        ->assertSee($sale->no_transaksi)
        ->assertSee($sale->nama_customer)
        ->searchTable('Sinta')
        ->assertCanSeeTableRecords([$sale])
        ->assertCanNotSeeTableRecords([$other])
        ->searchTable($other->no_transaksi)
        ->assertCanSeeTableRecords([$other])
        ->assertCanNotSeeTableRecords([$sale]);
});

test('sales report PDF renders the customer and sale number without a customer relationship', function () {
    $sale = salesOutputSale();
    $engine = app('dompdf.wrapper');

    Pdf::shouldReceive('loadView')->once()->andReturnUsing(function (string $view, array $data) use ($sale, $engine) {
        expect($view)->toBe('laporan.penjualan.pdf')
            ->and((float) $data['totalPenjualan'])->toBe(90000.0);
        $html = view($view, $data)->render();
        $this->assertStringContainsString($sale->nama_customer, $html);
        $this->assertStringContainsString($sale->no_transaksi, $html);
        $this->assertStringContainsString('Nomor penjualan', $html);
        $this->assertStringContainsString('Pelanggan', $html);

        return $engine->loadView($view, $data);
    });

    Livewire::test(ListLaporanPenjualan::class)
        ->callTableAction('cetak_pdf', $sale)
        ->assertFileDownloaded('laporan-penjualan-' . $sale->id . '.pdf');
});

test('legacy invoice action prints net received payments and cash change', function () {
    $sale = salesOutputSale();
    $engine = app('dompdf.wrapper');

    Pdf::shouldReceive('loadView')->once()->andReturnUsing(function (string $view, array $data) use ($sale, $engine) {
        expect($view)->toBe('pdf.invoice')
            ->and((float) $data['totalHarga'])->toBe(90000.0)
            ->and((float) $data['totalBayar'])->toBe(90000.0)
            ->and((float) $data['sisaBayar'])->toBe(0.0)
            ->and((float) $data['totalKembalian'])->toBe(30000.0);
        $html = view($view, $data)->render();
        $this->assertStringContainsString($sale->nama_customer, $html);
        $this->assertStringContainsString($sale->no_transaksi, $html);
        $this->assertStringContainsString('Rp30.000', $html);
        $this->assertStringNotContainsString('Rp120.000', $html);

        return $engine->loadView($view, $data);
    });

    Livewire::test(PrintInvoice::class, ['record' => $sale->getRouteKey()])
        ->callAction('print')
        ->assertFileDownloaded('invoice-' . str_replace('/', '-', $sale->no_transaksi) . '.pdf');
});

test('receipt endpoint uses the same net payment and change calculation', function () {
    $sale = salesOutputSale();
    $engine = app('dompdf.wrapper');

    Pdf::shouldReceive('loadView')->once()->andReturnUsing(function (string $view, array $data) use ($engine) {
        expect((float) $data['totalBayar'])->toBe(90000.0)
            ->and((float) $data['sisaBayar'])->toBe(0.0)
            ->and((float) $data['totalKembalian'])->toBe(30000.0);

        return $engine->loadView($view, $data);
    });

    $response = $this->get(route('order.print-struk', $sale))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->getContent())->toStartWith('%PDF');
});
