<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Payment extends Model
{
    protected $fillable = ['order_id', 'jumlah_bayar', 'sisa_bayar', 'kembalian', 'metode_pembayaran'];
    protected $casts = ['jumlah_bayar' => 'integer', 'sisa_bayar' => 'integer', 'kembalian' => 'integer'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                if ($this->isDirty()) {
                    throw ValidationException::withMessages(['jumlah_bayar' => 'Riwayat pembayaran tidak dapat diubah.']);
                }
                return true;
            }
            $order = Order::query()->whereKey($this->order_id)->lockForUpdate()->firstOrFail();
            $remaining = $order->remainingAmount();
            if ($order->status_transaksi === 'batal' || $remaining <= 0) {
                throw ValidationException::withMessages(['jumlah_bayar' => 'Pembayaran tidak dapat ditambahkan pada penjualan yang sudah lunas atau dibatalkan.']);
            }
            $amount = $this->getAttributes()['jumlah_bayar'] ?? null;
            if (! preg_match('/^[0-9]+$/', (string) $amount) || (int) $amount < 1 || (int) $amount > 2147483647) {
                throw ValidationException::withMessages(['jumlah_bayar' => 'Jumlah pembayaran harus berupa rupiah bulat lebih dari nol.']);
            }
            $this->metode_pembayaran ??= 'cash';
            if (! in_array($this->metode_pembayaran, ['cash', 'bank'], true)) {
                throw ValidationException::withMessages(['metode_pembayaran' => 'Pilih metode pembayaran tunai atau transfer bank.']);
            }
            if ($this->metode_pembayaran === 'bank' && $this->jumlah_bayar > $remaining) {
                throw ValidationException::withMessages(['jumlah_bayar' => 'Transfer bank tidak boleh melebihi sisa pembayaran.']);
            }
            $this->kembalian = max(0, $this->jumlah_bayar - (int) $remaining);
            $this->sisa_bayar = max(0, (int) $remaining - $this->jumlah_bayar);
            $saved = parent::save($options);
            if ($saved) {
                $order->refreshFinancials();
            }
            return $saved;
        }, 3);
    }

    public function delete()
    {
        throw ValidationException::withMessages(['pembayaran' => 'Riwayat pembayaran tidak dapat dihapus. Pengembalian uang harus dicatat melalui proses tersendiri.']);
    }
}
