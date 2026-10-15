<?php

namespace Database\Seeders;

use App\Models\Order;
use Carbon\CarbonInterface;
use Database\Seeders\Support\DemoPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        for ($day = 0; $day < 30; $day++) {
            for ($slot = 0; $slot < 4; $slot++) {
                $date = DemoPeriod::day($day);
                $scenario = ((int) floor($date->getTimestamp() / 86400) % 2) * 4 + $slot;
                $reference = 'DEMO-KAOS-'.$date->format('Ymd').'-'.str_pad((string) ($slot + 1), 3, '0', STR_PAD_LEFT);

                DB::transaction(function () use ($reference, $scenario, $day): void {
                    $order = Order::query()->where('no_transaksi', $reference)->lockForUpdate()->first();

                    // Keep reruns idempotent and never pay unrelated sales.
                    if (! $order || $order->status_transaksi === 'batal' || $order->payments()->exists()) {
                        return;
                    }

                    $total = (int) $order->orderDetails()->sum('subtotal');
                    if ($total <= 0) {
                        return;
                    }

                    $paidAt = $order->created_at->toImmutable()->setTimezone('Asia/Jakarta')->addMinutes(20);
                    $completedAt = $paidAt->addMinutes(10);

                    // Eight repeating scenarios keep all transaction and payment
                    // states visible without bypassing financial model rules.
                    switch ($scenario) {
                        case 0:
                            $this->pay($order, $total, 'cash', $paidAt);
                            break;
                        case 1:
                            $tendered = (int) (ceil($total / 50000) * 50000) + 10000;
                            $this->pay($order, $tendered, 'cash', $paidAt);
                            break;
                        case 2:
                        case 7:
                            $this->pay($order, $total, 'bank', $paidAt);
                            break;
                        case 3:
                            $this->pay($order, (int) floor($total * 0.4), 'bank', $paidAt);

                            return;
                        case 4:
                        case 6:
                            return;
                        case 5:
                            $firstInstallment = intdiv($total, 2);
                            $this->pay($order, $firstInstallment, 'cash', $paidAt);
                            $lastPaidAt = $day < 29 ? DemoPeriod::day($day + 1, 10, 15) : $paidAt->addMinutes(30);
                            $this->pay($order, $total - $firstInstallment, 'bank', $lastPaidAt);
                            $completedAt = $lastPaidAt->addMinutes(10);
                            break;
                    }

                    // A fully paid order can still await collection (scenario 7).
                    if ($scenario !== 7 && $order->fresh()->status_transaksi === 'proses') {
                        DemoPeriod::at($completedAt, fn () => $order->update(['status_transaksi' => 'selesai']));
                    }
                });
            }
        }
    }

    private function pay(Order $order, int $amount, string $method, CarbonInterface $date): void
    {
        DemoPeriod::at($date, function () use ($order, $amount, $method): void {
            $order->payments()->create([
                'jumlah_bayar' => $amount,
                'metode_pembayaran' => $method,
            ]);
        });
    }
}
