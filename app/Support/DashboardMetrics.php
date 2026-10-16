<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardMetrics
{
    /**
     * @return array<int, array{label: string, sales: float, orders: int}>
     */
    public static function monthlySummary(): array
    {
        $currentMonth = CarbonImmutable::now('Asia/Jakarta')->startOfMonth();
        $firstMonth = $currentMonth->subMonths(5);
        $months = [];

        for ($index = 0; $index < 6; $index++) {
            $month = $firstMonth->addMonths($index);
            $months[$month->format('Y-m')] = [
                'label' => $month->locale('id')->translatedFormat('M y'),
                'sales' => 0.0,
                'orders' => 0,
            ];
        }

        $orders = Order::query()
            ->select(['id', 'tanggal_order', 'status_transaksi', 'total_harga'])
            ->withSum('orderDetails as calculated_total', 'subtotal')
            ->where('status_transaksi', '!=', 'batal')
            ->where('tanggal_order', '>=', $firstMonth->toDateString())
            ->where('tanggal_order', '<', $currentMonth->addMonth()->toDateString());

        // Return at most six rows, rather than hydrating every sale on each widget render.
        $summaries = DB::query()
            ->fromSub($orders->toBase(), 'dashboard_orders')
            ->selectRaw('SUBSTR(tanggal_order, 1, 7) AS month, COUNT(*) AS orders')
            ->selectRaw('SUM(CASE WHEN status_transaksi = ? THEN COALESCE(calculated_total, total_harga, 0) ELSE 0 END) AS sales', ['selesai'])
            ->groupByRaw('SUBSTR(tanggal_order, 1, 7)')
            ->get();

        foreach ($summaries as $summary) {
            $months[$summary->month]['orders'] = (int) $summary->orders;
            $months[$summary->month]['sales'] = (float) $summary->sales;
        }

        return array_values($months);
    }

    /**
     * @return array{amount: float, orders: int}
     */
    public static function outstandingPayments(): array
    {
        $orders = Order::query()
            ->select(['id', 'total_harga'])
            ->withSum('orderDetails as calculated_total', 'subtotal')
            ->withSum('payments as paid_total', DB::raw('jumlah_bayar - COALESCE(kembalian, 0)'))
            ->where('status_transaksi', '!=', 'batal');

        // Each payment's sisa_bayar is a historical balance, not an amount to sum.
        $remaining = 'COALESCE(calculated_total, total_harga, 0) - COALESCE(paid_total, 0)';
        $summary = DB::query()
            ->fromSub($orders->toBase(), 'dashboard_payments')
            ->selectRaw("COALESCE(SUM(CASE WHEN {$remaining} > 0 THEN {$remaining} ELSE 0 END), 0) AS amount")
            ->selectRaw("COUNT(CASE WHEN {$remaining} > 0 THEN 1 END) AS orders")
            ->first();

        return ['amount' => (float) $summary->amount, 'orders' => (int) $summary->orders];
    }

    /**
     * @return array{total: int, available: int}
     */
    public static function productStockSummary(): array
    {
        $products = Product::query()->select('id')->withSum('details', 'stok');
        $summary = DB::query()
            ->fromSub($products->toBase(), 'dashboard_products')
            ->selectRaw('COUNT(*) AS total, COUNT(CASE WHEN details_sum_stok > 0 THEN 1 END) AS available')
            ->first();

        return ['total' => (int) $summary->total, 'available' => (int) $summary->available];
    }

    public static function orderTotal(Order $order): float
    {
        // Detail subtotals are available even when the stored order total has not been refreshed.
        return (float) ($order->calculated_total ?? $order->total_harga);
    }
}
