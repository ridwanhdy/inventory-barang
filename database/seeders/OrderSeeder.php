<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\Produksi;
use App\Models\User;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderSeeder extends Seeder
{
    private const CUSTOMERS = [
        'Andi Pratama', 'Siti Nurhaliza', 'Budi Santoso', 'Dewi Lestari',
        'Rizky Ramadhan', 'Nadia Putri', 'Agus Setiawan', 'Rina Wulandari',
        'Dimas Saputra', 'Ayu Permatasari', 'Fajar Hidayat', 'Intan Puspita',
        'Hendra Wijaya', 'Fitri Handayani', 'Yoga Maulana', 'Ratna Sari',
        'Arif Kurniawan', 'Maya Anggraini', 'Eko Susanto', 'Lia Oktaviani',
        'Toko Sandang Jaya', 'Komunitas Lari Pagi', 'Warung Kopi Senja', 'Studio Kreasi Bandung',
        'Panitia Karang Taruna', 'Toko Busana Mandiri', 'Tim Futsal Garuda', 'Kedai Roti Melati',
        'Komunitas Sepeda Santai', 'Koperasi Karyawan Sejahtera',
    ];

    public function run(): void
    {
        $cashier = User::query()->where('email', 'kasir@kasir.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        for ($day = 0; $day < 30; $day++) {
            $date = DemoPeriod::day($day);
            $completedByDate = fn ($query) => $query->where('status', Produksi::COMPLETED)
                ->where('catatan', 'like', 'DEMO-KAOS-PRODUKSI-%')
                ->whereDate('produksi_selesai', '<=', $date->toDateString());
            $products = Product::query()->where('nama_product', 'like', 'Kaos Polos%')
                ->whereHas('produksi', $completedByDate)
                ->withSum(['produksi as completed_quantity' => $completedByDate], 'jumlah_hasil')
                ->withSum('details as available_stock', 'stok')
                ->orderBy('id')->get();

            // Future production is already in physical stock. Only completed output
            // available on the sale date may be reserved for a historical sale.
            $reserved = DB::table('order_details')
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->where('orders.status_transaksi', '!=', 'batal')
                ->whereDate('orders.tanggal_order', '<=', $date->toDateString())
                ->selectRaw('order_details.product_id, SUM(order_details.quantity) AS quantity')
                ->groupBy('order_details.product_id')->pluck('quantity', 'product_id')->all();

            for ($slot = 0; $slot < 4; $slot++) {
                $sequence = $day * 4 + $slot;
                $scenario = ((int) floor($date->getTimestamp() / 86400) % 2) * 4 + $slot;
                $reference = 'DEMO-KAOS-'.$date->format('Ymd').'-'.str_pad((string) ($slot + 1), 3, '0', STR_PAD_LEFT);

                if (Order::query()->where('no_transaksi', $reference)->exists()) {
                    continue;
                }

                $items = $this->itemsForOrder($products, $reserved, $sequence);
                $orderedAt = DemoPeriod::day($day, 13 + $slot, ($day * 7 + $slot * 11) % 45);
                $cancelled = $scenario === 6;

                DB::transaction(function () use ($reference, $sequence, $slot, $day, $cashier, $admin, $items, $orderedAt, $cancelled): void {
                    $order = DemoPeriod::at($orderedAt, function () use ($reference, $sequence, $slot, $day, $cashier, $admin, $items, $orderedAt): Order {
                        $order = Order::create([
                            'no_transaksi' => $reference,
                            'nama_customer' => self::CUSTOMERS[($day + $slot * 7) % count(self::CUSTOMERS)],
                            'users_id' => $sequence % 10 === 0 ? $admin->id : $cashier->id,
                            'tanggal_order' => $orderedAt->toDateString(),
                        ]);

                        foreach ($items as $item) {
                            $order->orderDetails()->create([
                                'product_id' => $item['product']->id,
                                'quantity' => $item['quantity'],
                                'harga' => $item['product']->harga_jual,
                            ]);
                        }

                        return $order;
                    });

                    if ($cancelled) {
                        DemoPeriod::at($orderedAt->addMinutes(15), fn () => $order->cancelSale());
                    }
                });

                if (! $cancelled) {
                    foreach ($items as $item) {
                        $product = $item['product'];
                        $product->available_stock -= $item['quantity'];
                        $reserved[$product->id] = ($reserved[$product->id] ?? 0) + $item['quantity'];
                    }
                }
            }
        }
    }

    /** @return array<int, array{product: Product, quantity: int}> */
    private function itemsForOrder(Collection $products, array $reserved, int $sequence): array
    {
        $items = [];
        $selected = [];
        $productCount = $products->count();

        for ($line = 0; $line < 1 + $sequence % 3; $line++) {
            $quantity = 1 + ($sequence + $line) % 3;
            $start = $productCount > 0 ? ($sequence * 17 + $line * 31) % $productCount : 0;
            $chosen = null;

            for ($attempt = 0; $attempt < $productCount; $attempt++) {
                $product = $products[($start + $attempt) % $productCount];
                $historicalStock = (int) $product->completed_quantity - (int) ($reserved[$product->id] ?? 0);

                if (! isset($selected[$product->id]) && $historicalStock >= $quantity && (float) $product->available_stock >= $quantity) {
                    $chosen = $product;
                    break;
                }
            }

            if (! $chosen) {
                throw new RuntimeException('Stok Kaos Polos hasil produksi pada tanggal penjualan belum mencukupi. Jalankan ProduksiSeeder sebelum OrderSeeder.');
            }

            $selected[$chosen->id] = true;
            $items[] = ['product' => $chosen, 'quantity' => $quantity];
        }

        return $items;
    }
}
