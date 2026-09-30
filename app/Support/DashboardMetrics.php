<?php

namespace App\Support;

use App\Models\Order;
use Carbon\CarbonImmutable;

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
            ->where('tanggal_order', '<', $currentMonth->addMonth()->toDateString())
            ->cursor();

        foreach ($orders as $order) {
            $month = $order->tanggal_order->format('Y-m');
            $months[$month]['orders']++;

            if ($order->status_transaksi === 'selesai') {
                $months[$month]['sales'] += self::orderTotal($order);
            }
        }

        return array_values($months);
    }

    /**
     * @return array{amount: float, orders: int}
     */
    public static function outstandingPayments(): array
    {
        $summary = ['amount' => 0.0, 'orders' => 0];
        $orders = Order::query()
            ->select(['id', 'total_harga'])
            ->withSum('orderDetails as calculated_total', 'subtotal')
            ->withSum('payments as paid_total', 'jumlah_bayar')
            ->where('status_transaksi', '!=', 'batal')
            ->cursor();

        foreach ($orders as $order) {
            // Each payment's sisa_bayar is a historical balance, not an amount to sum.
            $remaining = max(0, self::orderTotal($order) - (float) $order->paid_total);

            if ($remaining > 0) {
                $summary['amount'] += $remaining;
                $summary['orders']++;
            }
        }

        return $summary;
    }

    public static function orderTotal(Order $order): float
    {
        // Detail subtotals are available even when the stored order total has not been refreshed.
        return (float) ($order->calculated_total ?? $order->total_harga);
    }
}
