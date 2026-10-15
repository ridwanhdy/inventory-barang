<?php

use App\Models\BahanBaku;
use App\Models\Order;
use App\Models\Product;
use App\Models\Produksi;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Support\DemoPeriod;
use Database\Seeders\Support\KaosPolosCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('kaos polos demo covers a month with reconciled inventory and payments and can be seeded twice', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-15 23:59:59', 'Asia/Jakarta'));
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@admin.com')->firstOrFail();
    $cashier = User::where('email', 'kasir@kasir.com')->firstOrFail();
    expect($admin->created_at->toDateTimeString())->toBe('2026-09-15 17:00:00')
        ->and(User::count())->toBe(2)
        ->and($admin->role)->toBe('admin')
        ->and(Hash::check('password', $admin->password))->toBeTrue()
        ->and($cashier->role)->toBe('kasir')
        ->and(Hash::check('kasir', $cashier->password))->toBeTrue();

    $products = Product::with(['details', 'produksi'])->orderBy('id')->get();
    expect($products)->toHaveCount(1152)
        ->and($products->pluck('ukuran')->unique()->sort()->values()->all())->toBe(collect(KaosPolosCatalog::sizes())->sort()->values()->all())
        ->and($products->pluck('warna')->unique()->sort()->values()->all())->toBe(collect(KaosPolosCatalog::colors())->sort()->values()->all())
        ->and($products->pluck('bahan')->unique()->sort()->values()->all())->toBe(collect(array_keys(KaosPolosCatalog::fabrics()))->sort()->values()->all())
        ->and($products->map(fn (Product $product) => $product->bahan.'|'.$product->warna.'|'.$product->ukuran)->unique())->toHaveCount(1152)
        ->and($products->every(fn (Product $product) => str_starts_with($product->nama_product, 'Kaos Polos ')))->toBeTrue()
        ->and(DB::table('bahan_bakus')->count())->toBe(148)
        ->and(DB::table('pemasoks')->count())->toBe(3);

    $orders = Order::with(['orderDetails', 'payments'])->orderBy('tanggal_order')->orderBy('id')->get();
    expect($orders)->toHaveCount(120)
        ->and($orders->pluck('tanggal_order')->map->toDateString()->unique())->toHaveCount(30)
        ->and($orders->first()->tanggal_order->toDateString())->toBe('2026-09-16')
        ->and($orders->last()->tanggal_order->toDateString())->toBe('2026-10-15')
        ->and($orders->pluck('status_transaksi')->unique()->sort()->values()->all())->toBe(['batal', 'proses', 'selesai'])
        ->and($orders->pluck('status_pembayaran')->unique()->sort()->values()->all())->toBe(['belum_bayar', 'cicilan', 'lunas'])
        ->and($orders->pluck('users_id')->unique()->sort()->values()->all())->toBe(collect([$admin->id, $cashier->id])->sort()->values()->all())
        ->and(DB::table('pembelians')->distinct()->count('tanggal_pembelian'))->toBe(30)
        ->and($products->flatMap->produksi->pluck('produksi_mulai')->map->toDateString()->unique())->toHaveCount(30)
        ->and(Produksi::query()->distinct()->pluck('status')->sort()->values()->all())->toBe(['Batal', 'Draft', 'Proses', 'Selesai']);

    $reserved = [];
    $payments = $orders->flatMap->payments;
    expect($payments->pluck('metode_pembayaran')->unique()->sort()->values()->all())->toBe(['bank', 'cash'])
        ->and($payments->contains(fn ($payment) => $payment->kembalian > 0))->toBeTrue()
        ->and($orders->contains(fn (Order $order) => $order->payments->count() === 2))->toBeTrue();

    foreach ($orders as $order) {
        expect($order->orderDetails)->not->toBeEmpty();
        $total = $order->orderDetails->sum('subtotal');
        $paid = $order->payments->sum(fn ($payment) => $payment->jumlah_bayar - $payment->kembalian);
        expect($order->subtotal)->toBe($total)
            ->and($order->total_harga)->toBe($total)
            ->and($order->status_pembayaran)->toBe($paid <= 0 ? 'belum_bayar' : ($paid >= $total ? 'lunas' : 'cicilan'));

        $balance = $total;
        foreach ($order->payments->sortBy('id') as $payment) {
            $balance -= $payment->jumlah_bayar - $payment->kembalian;
            expect($payment->sisa_bayar)->toBe($balance)
                ->and($payment->created_at->gte($order->created_at))->toBeTrue(
                    $order->no_transaksi.': pembayaran '.$payment->created_at.' mendahului order '.$order->created_at,
                );
        }

        if ($order->status_transaksi === 'batal') {
            expect($order->payments)->toBeEmpty();
        }

        foreach ($order->orderDetails as $detail) {
            expect($detail->subtotal)->toBe($detail->quantity * $detail->harga);
            $product = $products->firstWhere('id', $detail->product_id);
            $available = $product->produksi->where('status', Produksi::COMPLETED)
                ->filter(fn (Produksi $batch) => $batch->produksi_selesai->toDateString() <= $order->tanggal_order->toDateString())
                ->sum('jumlah_hasil');
            expect($available - ($reserved[$product->id] ?? 0))->toBeGreaterThanOrEqual($detail->quantity);
            if ($order->status_transaksi !== 'batal') {
                $reserved[$product->id] = ($reserved[$product->id] ?? 0) + $detail->quantity;
            }
        }
    }

    foreach ($products as $product) {
        foreach ($product->produksi as $production) {
            if ($production->hasStarted()) {
                expect($production->produksi_dimulai_at->gte($production->created_at))->toBeTrue()
                    ->and($production->updated_at->gte($production->produksi_dimulai_at))->toBeTrue();
            }
        }
        $output = $product->produksi->where('status', Produksi::COMPLETED)->sum('jumlah_hasil');
        $expectedStock = $output - ($reserved[$product->id] ?? 0);
        expect($output)->toBeGreaterThan(0)
            ->and($product->details)->toHaveCount(1)
            ->and((int) $product->details->sum('stok'))->toBe($expectedStock)
            ->and((int) $product->stok)->toBe($expectedStock);
    }

    $receipts = DB::table('pembelian_details')->selectRaw('bahan_baku_id, SUM(quantity) as quantity')->groupBy('bahan_baku_id')->pluck('quantity', 'bahan_baku_id');
    $consumed = DB::table('produksi_details')->join('produksis', 'produksis.id', '=', 'produksi_details.produksi_id')
        ->whereNotNull('produksis.produksi_dimulai_at')->selectRaw('bahan_baku_id, SUM(jumlah_digunakan - jumlah_dikembalikan) as quantity')
        ->groupBy('bahan_baku_id')->pluck('quantity', 'bahan_baku_id');
    $historyBalances = DB::table('bahan_baku_histories')
        ->selectRaw("bahan_baku_id, SUM(CASE WHEN tipe_perubahan = 'tambah' THEN jumlah_perubahan ELSE -jumlah_perubahan END) as balance")
        ->groupBy('bahan_baku_id')->pluck('balance', 'bahan_baku_id');
    expect($historyBalances)->toHaveCount(148);
    foreach (BahanBaku::with('details')->get() as $material) {
        $expectedStock = round((float) $receipts->get($material->id, 0) - (float) $consumed->get($material->id, 0), 2);
        expect($material->details)->toHaveCount(1)
            ->and($expectedStock)->toBeGreaterThanOrEqual(0)
            ->and(round($material->details->sum('stok'), 2))->toBe($expectedStock)
            ->and(round((float) $historyBalances->get($material->id), 2))->toBe($expectedStock);
    }

    $tables = ['users', 'satuans', 'kategoris', 'pemasoks', 'products', 'product_details', 'bahan_bakus', 'bahan_baku_details', 'pembelians', 'pembelian_details', 'produksis', 'produksi_details', 'orders', 'order_details', 'payments', 'bahan_baku_histories'];
    foreach ($tables as $table) {
        expect(DB::table($table)->where('created_at', '<', '2026-09-15 17:00:00')->exists())->toBeFalse()
            ->and(DB::table($table)->where('updated_at', '>', '2026-10-15 16:59:59')->exists())->toBeFalse();
    }
    $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all();
    $productStock = DB::table('product_details')->orderBy('id')->pluck('stok', 'id')->all();
    $materialStock = DB::table('bahan_baku_details')->orderBy('id')->pluck('stok', 'id')->all();
    // A legacy plain-shirt record must not be mistaken for a new catalog variant.
    $legacy = Product::create([
        'nama_product' => 'Kaos Polos Premium Hitam',
        'kategori_id' => $products->first()->kategori_id,
        'satuan_id' => $products->first()->satuan_id,
        'ukuran' => 'M', 'warna' => 'Hitam', 'bahan' => 'Cotton Combed 30s',
        'harga_jual' => 45000, 'foto' => '',
    ]);
    $counts['products']++;
    $counts['product_details']++;
    $productStock[$legacy->details()->firstOrFail()->id] = (float) 0;
    $this->seed(DatabaseSeeder::class);
    expect(collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()])->all())->toBe($counts)
        ->and(DB::table('product_details')->orderBy('id')->pluck('stok', 'id')->all())->toBe($productStock)
        ->and(DB::table('bahan_baku_details')->orderBy('id')->pluck('stok', 'id')->all())->toBe($materialStock)
        ->and(now('Asia/Jakarta')->toDateTimeString())->toBe('2026-10-15 23:59:59');

    // Moving the rolling window must not turn an older unpaid order into a paid sale.
    $oldOrders = DB::table('orders')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    $oldPayments = DB::table('payments')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    Carbon::setTestNow(Carbon::parse('2026-10-18 23:59:59', 'Asia/Jakarta'));
    $this->seed(DatabaseSeeder::class);
    expect(Order::count())->toBe(132)
        ->and(DB::table('orders')->whereIn('id', array_column($oldOrders, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all())->toBe($oldOrders)
        ->and(DB::table('payments')->whereIn('id', array_column($oldPayments, 'id'))->orderBy('id')->get()->map(fn ($row) => (array) $row)->all())->toBe($oldPayments)
        ->and(DB::table('payments')->join('orders', 'orders.id', '=', 'payments.order_id')->whereColumn('payments.created_at', '<', 'orders.created_at')->exists())->toBeFalse();
});

test('demo period remains stable under historical clocks and restores time after failures', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-02 12:00:00', 'Asia/Jakarta'));
    DemoPeriod::run(function () {
        expect(DemoPeriod::start()->toDateString())->toBe('2025-12-04')
            ->and(DemoPeriod::end()->toDateString())->toBe('2026-01-02');
        try {
            DemoPeriod::at(DemoPeriod::day(0, 7), function () {
                expect(DemoPeriod::end()->toDateString())->toBe('2026-01-02')
                    ->and(now('Asia/Jakarta')->toDateTimeString())->toBe('2025-12-04 07:00:00');
                throw new RuntimeException('clock restoration');
            });
        } catch (RuntimeException $exception) {
            expect($exception->getMessage())->toBe('clock restoration');
        }
    });
    expect(now('Asia/Jakarta')->toDateTimeString())->toBe('2026-01-02 12:00:00');
});
