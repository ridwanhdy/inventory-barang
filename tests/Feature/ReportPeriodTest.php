<?php

use App\Filament\Resources\LaporanPenjualanResource\Pages\ListLaporanPenjualan;
use App\Filament\Resources\LaporanStokBahanBakuResource\Pages\ListLaporanStokBahanBaku;
use App\Filament\Resources\LaporanStokProdukResource\Pages\ListLaporanStokProduk;
use App\Models\BahanBakuDetail;
use App\Models\Order;
use App\Models\ProductDetail;
use App\Models\User;
use App\Support\ReportPeriod;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class ReportPeriodPanelUser extends User implements FilamentUser
{
    protected $table = 'users';

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }
}

beforeEach(function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs(ReportPeriodPanelUser::findOrFail($user->id));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function reportPeriodSale(string $date, array $attributes = []): Order
{
    $id = DB::table('orders')->insertGetId(array_replace([
        'no_transaksi' => 'REPORT/'.(DB::table('orders')->count() + 1),
        'nama_customer' => 'Pelanggan laporan',
        'users_id' => auth()->id(),
        'tanggal_order' => $date,
        'status_transaksi' => 'selesai',
        'status_pembayaran' => 'lunas',
        'subtotal' => 10000,
        'total_harga' => 10000,
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));

    return Order::findOrFail($id);
}

function reportPeriodStock(string $kind, string $updatedAt, string $createdAt = '2020-01-01 00:00:00', string $name = 'Stok laporan')
{
    $category = DB::table('kategoris')->insertGetId(['nama_kategori' => 'Kategori laporan']);
    $unit = DB::table('satuans')->insertGetId(['nama_satuan' => 'pcs']);

    if ($kind === 'produk') {
        $product = DB::table('products')->insertGetId([
            'nama_product' => $name, 'kategori_id' => $category, 'satuan_id' => $unit,
            'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Katun', 'harga_jual' => 30000,
            'foto' => '', 'created_at' => $createdAt, 'updated_at' => $updatedAt,
        ]);
        $id = DB::table('product_details')->insertGetId([
            'product_id' => $product, 'stok' => 7, 'created_at' => $createdAt, 'updated_at' => $updatedAt,
        ]);

        return ProductDetail::findOrFail($id);
    }

    $material = DB::table('bahan_bakus')->insertGetId([
        'nama_bahan' => $name, 'kategori_id' => $category, 'satuan_id' => $unit,
        'jenis' => 'bahan baku', 'created_at' => $createdAt, 'updated_at' => $updatedAt,
    ]);
    $id = DB::table('bahan_baku_details')->insertGetId([
        'bahan_baku_id' => $material, 'stok' => 7,
        'created_at' => $createdAt, 'updated_at' => $updatedAt,
    ]);

    return BahanBakuDetail::findOrFail($id);
}

test('period bounds include complete Jakarta days months and years', function (array $period, string $start, string $end) {
    [$first, $last] = ReportPeriod::bounds($period);

    expect($first->format('Y-m-d H:i:s.u'))->toBe($start.' 00:00:00.000000')
        ->and($last->format('Y-m-d H:i:s.u'))->toBe($end.' 23:59:59.999999')
        ->and($first->timezoneName)->toBe('Asia/Jakarta')
        ->and($last->timezoneName)->toBe('Asia/Jakarta');
})->with([
    'same date' => [['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-15', 'tanggal_akhir' => '2026-10-15'], '2026-10-15', '2026-10-15'],
    'cross year and leap February' => [['jenis' => 'bulan', 'bulan_awal' => 12, 'tahun_awal' => 2023, 'bulan_akhir' => 2, 'tahun_akhir' => 2024], '2023-12-01', '2024-02-29'],
    'complete years' => [['jenis' => 'tahun', 'tahun_awal' => 2024, 'tahun_akhir' => 2025], '2024-01-01', '2025-12-31'],
]);

test('all periods ignore stale fields and absent period types do not restrict reports', function () {
    $stale = [
        'tanggal_awal' => '2027-01-01', 'tanggal_akhir' => '2020-01-01',
        'bulan_awal' => 12, 'bulan_akhir' => 1, 'tahun_awal' => 2027, 'tahun_akhir' => 2020,
    ];

    expect(ReportPeriod::bounds([]))->toBe([null, null])
        ->and(ReportPeriod::bounds(['jenis' => 'all'] + $stale))->toBe([null, null]);

    $sale = reportPeriodSale('2026-10-15');
    Livewire::test(ListLaporanPenjualan::class)
        ->set('tableDeferredFilters.periode', ['jenis' => 'all'] + $stale)
        ->call('applyTableFilters')
        ->assertHasNoErrors()
        ->assertCanSeeTableRecords([$sale]);
});

test('sales date range includes both endpoints and only completed transactions', function () {
    $first = reportPeriodSale('2026-10-01');
    $last = reportPeriodSale('2026-10-31');
    $before = reportPeriodSale('2026-09-30');
    $after = reportPeriodSale('2026-11-01');
    $unfinished = reportPeriodSale('2026-10-15', ['status_transaksi' => 'proses']);
    $cancelled = reportPeriodSale('2026-10-15', ['status_transaksi' => 'batal']);

    Livewire::test(ListLaporanPenjualan::class)
        ->filterTable('periode', ['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-01', 'tanggal_akhir' => '2026-10-31'])
        ->assertCanSeeTableRecords([$first, $last])
        ->assertCanNotSeeTableRecords([$before, $after, $unfinished, $cancelled]);
});

test('month and year modes ignore stale date fields and include cross year endpoints', function (array $period, string $firstDate, string $lastDate) {
    $first = reportPeriodSale($firstDate);
    $last = reportPeriodSale($lastDate);
    $before = reportPeriodSale(date('Y-m-d', strtotime($firstDate.' -1 day')));
    $after = reportPeriodSale(date('Y-m-d', strtotime($lastDate.' +1 day')));

    Livewire::test(ListLaporanPenjualan::class)
        ->filterTable('periode', $period + ['tanggal_awal' => '2030-12-31', 'tanggal_akhir' => '2020-01-01'])
        ->assertCanSeeTableRecords([$first, $last])
        ->assertCanNotSeeTableRecords([$before, $after]);
})->with([
    'month' => [['jenis' => 'bulan', 'bulan_awal' => 12, 'tahun_awal' => 2023, 'bulan_akhir' => 2, 'tahun_akhir' => 2024], '2023-12-01', '2024-02-29'],
    'year' => [['jenis' => 'tahun', 'tahun_awal' => 2024, 'tahun_akhir' => 2025, 'bulan_awal' => 12, 'bulan_akhir' => 1], '2024-01-01', '2025-12-31'],
]);

test('incomplete and reversed periods cannot be applied or exported', function (array $period, string $field) {
    reportPeriodSale('2026-10-15');
    Pdf::shouldReceive('loadView')->never();

    Livewire::test(ListLaporanPenjualan::class)
        ->set('tableDeferredFilters.periode', $period)
        ->call('applyTableFilters')
        ->assertHasErrors(['tableDeferredFilters.periode.'.$field]);

    Livewire::test(ListLaporanPenjualan::class)
        ->set('tableDeferredFilters.periode', $period)
        ->callAction('cetak_pdf')
        ->assertHasErrors(['tableDeferredFilters.periode.'.$field])
        ->assertNoFileDownloaded();
})->with([
    'missing date end' => [['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-15'], 'tanggal_akhir'],
    'reversed dates' => [['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-15', 'tanggal_akhir' => '2026-10-01'], 'tanggal_akhir'],
    'missing month year' => [['jenis' => 'bulan', 'bulan_awal' => 1, 'bulan_akhir' => 2, 'tahun_awal' => 2026], 'tahun_akhir'],
    'reversed months' => [['jenis' => 'bulan', 'bulan_awal' => 12, 'bulan_akhir' => 1, 'tahun_awal' => 2026, 'tahun_akhir' => 2026], 'bulan_akhir'],
    'missing year end' => [['jenis' => 'tahun', 'tahun_awal' => 2026], 'tahun_akhir'],
    'reversed years' => [['jenis' => 'tahun', 'tahun_awal' => 2026, 'tahun_akhir' => 2025], 'tahun_akhir'],
]);

test('stock ranges use last update and include the last Jakarta day of leap February', function (string $page, string $kind) {
    $first = reportPeriodStock($kind, '2024-01-31 17:00:00');
    $last = reportPeriodStock($kind, '2024-02-29 16:59:59');
    $before = reportPeriodStock($kind, '2024-01-31 16:59:59', '2024-02-10 00:00:00');
    $after = reportPeriodStock($kind, '2024-02-29 17:00:00', '2024-02-10 00:00:00');

    Livewire::test($page)
        ->filterTable('periode', ['jenis' => 'bulan', 'bulan_awal' => 2, 'bulan_akhir' => 2, 'tahun_awal' => 2024, 'tahun_akhir' => 2024])
        ->assertCanSeeTableRecords([$first, $last])
        ->assertCanNotSeeTableRecords([$before, $after]);
})->with([
    'products' => [ListLaporanStokProduk::class, 'produk'],
    'materials' => [ListLaporanStokBahanBaku::class, 'bahan'],
]);

test('sales PDF applies pending period payment and search filters and exports all sorted rows with the matching total', function () {
    $period = ['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-01', 'tanggal_akhir' => '2026-10-31'];
    $sales = collect(range(1, 12))->map(fn (int $number) => reportPeriodSale('2026-10-'.str_pad((string) ($number + 4), 2, '0', STR_PAD_LEFT), [
        'nama_customer' => 'Target pelanggan '.$number, 'total_harga' => $number * 1000, 'subtotal' => $number * 1000,
    ]));
    reportPeriodSale('2026-09-30', ['nama_customer' => 'Target sebelum']);
    reportPeriodSale('2026-11-01', ['nama_customer' => 'Target sesudah']);
    reportPeriodSale('2026-10-15', ['nama_customer' => 'Target belum bayar', 'status_pembayaran' => 'belum_bayar']);
    reportPeriodSale('2026-10-15', ['nama_customer' => 'Pelanggan lain']);
    reportPeriodSale('2026-10-15', ['nama_customer' => 'Target batal', 'status_transaksi' => 'batal']);
    $engine = app('dompdf.wrapper');

    Pdf::shouldReceive('loadView')->once()->andReturnUsing(function (string $view, array $data) use ($period, $sales, $engine) {
        expect($view)->toBe('laporan.penjualan.pdf')
            ->and($data['orders']->pluck('id')->all())->toBe($sales->reverse()->pluck('id')->values()->all())
            ->and((int) $data['totalPenjualan'])->toBe(78000)
            ->and($data['periodeLabel'])->toBe(ReportPeriod::label($period))
            ->and($data['filterLabel'])->toContain('Target');
        $html = view($view, $data)->render();
        $this->assertStringContainsString(e(ReportPeriod::label($period)), $html);

        return $engine->loadView($view, $data);
    });

    Livewire::test(ListLaporanPenjualan::class)
        ->set('tableDeferredFilters.periode', $period)
        ->set('tableDeferredFilters.status_pembayaran', ['value' => 'lunas'])
        ->searchTable('Target')
        ->sortTable('total_harga', 'desc')
        ->callAction('cetak_pdf')
        ->assertHasNoErrors()
        ->assertFileDownloaded('laporan-penjualan.pdf');
});

test('empty filtered reports show a notification and do not create a PDF', function (string $page) {
    Pdf::shouldReceive('loadView')->never();

    Livewire::test($page)
        ->set('tableDeferredFilters.periode', ['jenis' => 'tahun', 'tahun_awal' => 2020, 'tahun_akhir' => 2020])
        ->callAction('cetak_pdf')
        ->assertNotified('Tidak ada data untuk dicetak')
        ->assertNoFileDownloaded();
})->with([ListLaporanPenjualan::class, ListLaporanStokProduk::class, ListLaporanStokBahanBaku::class]);

test('stock PDFs preserve the chosen period search and current stock explanation', function (string $page, string $kind, string $view, string $filename) {
    $period = ['jenis' => 'tanggal', 'tanggal_awal' => '2026-10-01', 'tanggal_akhir' => '2026-10-31'];
    $included = reportPeriodStock($kind, '2026-10-31 16:59:59', '2020-01-01 00:00:00', 'Target stok');
    reportPeriodStock($kind, '2026-10-31 17:00:00', '2026-10-15 00:00:00', 'Target di luar periode');
    reportPeriodStock($kind, '2026-10-15 00:00:00', '2020-01-01 00:00:00', 'Nama lain');
    $engine = app('dompdf.wrapper');

    Pdf::shouldReceive('loadView')->once()->andReturnUsing(function (string $actualView, array $data) use ($period, $included, $view, $engine) {
        expect($actualView)->toBe($view)
            ->and($data['details']->pluck('id')->all())->toBe([$included->id])
            ->and($data['periodeLabel'])->toBe(ReportPeriod::label($period))
            ->and($data['filterLabel'])->toContain('Target');
        $html = view($actualView, $data)->render();
        $this->assertStringContainsString('Stok saat ini', $html);
        $this->assertStringContainsString('waktu pembaruan stok', $html);
        $this->assertStringContainsString('31/10/2026 23:59', $html);
        $this->assertStringContainsString(e(ReportPeriod::label($period)), $html);

        return $engine->loadView($actualView, $data);
    });

    Livewire::test($page)
        ->set('tableDeferredFilters.periode', $period)
        ->searchTable('Target')
        ->callAction('cetak_pdf')
        ->assertHasNoErrors()
        ->assertFileDownloaded($filename);
})->with([
    'products' => [ListLaporanStokProduk::class, 'produk', 'laporan.stok_produk.pdf', 'laporan-stok-produk.pdf'],
    'materials' => [ListLaporanStokBahanBaku::class, 'bahan', 'laporan.stok_bahan_baku.pdf', 'laporan-stok-bahan-baku.pdf'],
]);

test('resetting the period restores all completed sales', function () {
    $inside = reportPeriodSale('2026-10-15');
    $outside = reportPeriodSale('2025-01-01');

    Livewire::test(ListLaporanPenjualan::class)
        ->set('tableDeferredFilters.periode', ['jenis' => 'tahun', 'tahun_awal' => 2026, 'tahun_akhir' => 2026])
        ->call('applyTableFilters')
        ->assertCanSeeTableRecords([$inside])
        ->assertCanNotSeeTableRecords([$outside])
        ->resetTableFilters()
        ->assertCanSeeTableRecords([$inside, $outside]);
});

test('sales summary counts legacy transactions without a transaction number', function () {
    $legacy = reportPeriodSale('2026-10-15', ['no_transaksi' => null]);
    $numbered = reportPeriodSale('2026-10-16');

    Livewire::test(ListLaporanPenjualan::class)
        ->assertCanSeeTableRecords([$legacy, $numbered])
        ->assertTableColumnSummarySet('no_transaksi', 'jumlah_transaksi', 2);
});
