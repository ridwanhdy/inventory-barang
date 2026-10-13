<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    protected $fillable = [
        'no_transaksi', 'nama_customer', 'users_id', 'tanggal_order',
        'status_transaksi', 'status_pembayaran', 'subtotal', 'total_harga',
    ];

    protected $casts = ['tanggal_order' => 'date', 'subtotal' => 'integer', 'total_harga' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paidAmount(): float
    {
        return max(0, (float) $this->payments()
            ->selectRaw('COALESCE(SUM(jumlah_bayar - kembalian), 0) AS paid')->value('paid'));
    }

    public function remainingAmount(): float
    {
        if ($this->status_transaksi === 'batal') {
            return 0;
        }
        return max(0, (float) $this->orderDetails()->sum('subtotal') - $this->paidAmount());
    }

    public function canEditItems(): bool
    {
        return $this->status_transaksi === 'proses' && ! $this->payments()->exists();
    }

    public function canCancel(): bool
    {
        return $this->exists && $this->status_transaksi === 'proses' && ! $this->payments()->exists();
    }

    public function cancelSale(): void
    {
        DB::transaction(function (): void {
            $sale = static::query()->lockForUpdate()->findOrFail($this->getKey());
            if ($sale->status_transaksi === 'batal') {
                $this->refresh();
                return;
            }
            if (! $sale->canCancel()) {
                throw ValidationException::withMessages([
                    'status_transaksi' => 'Penjualan hanya dapat dibatalkan saat masih diproses dan belum memiliki pembayaran. Pembayaran yang sudah dicatat memerlukan pengembalian uang terlebih dahulu.',
                ]);
            }
            $sale->update(['status_transaksi' => 'batal']);
            $this->refresh();
        }, 3);
    }

    public function refreshFinancials(): void
    {
        if (! $this->exists) {
            return;
        }
        DB::transaction(function (): void {
            $locked = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            $total = (int) $locked->orderDetails()->sum('subtotal');
            $paid = $locked->paidAmount();
            $locked->updateQuietly([
                'subtotal' => $total,
                'total_harga' => $total,
                'status_pembayaran' => $paid <= 0 ? 'belum_bayar' : ($paid >= $total ? 'lunas' : 'cicilan'),
            ]);
            $this->refresh();
        }, 3);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $locked = static::query()->lockForUpdate()->findOrFail($this->getKey());
                $changes = $this->getDirty();
                $this->setRawAttributes($locked->getAttributes(), true);
                $this->setRawAttributes(array_replace($this->getAttributes(), $changes));
            }
            return parent::save($options);
        }, 3);
    }

    public function delete()
    {
        throw ValidationException::withMessages([
            'penjualan' => 'Riwayat penjualan tidak dapat dihapus. Gunakan pembatalan untuk penjualan yang belum dibayar.',
        ]);
    }

    protected static function booted()
    {
        static::creating(function (Order $order): void {
            if (isset($order->status_transaksi) && $order->status_transaksi !== 'proses') {
                throw ValidationException::withMessages(['status_transaksi' => 'Penjualan baru harus berstatus Proses.']);
            }
            $order->status_transaksi = 'proses';
            $order->tanggal_order ??= now();
        });
        static::created(function (Order $order): void {
            if (blank($order->no_transaksi)) {
                // The database ID keeps numbers unique during simultaneous sales.
                $order->updateQuietly([
                    'no_transaksi' => 'INV/'.$order->tanggal_order->format('Y/m').'/'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
        static::saving(function (Order $order): void {
            $total = $order->exists ? (int) $order->orderDetails()->sum('subtotal') : 0;
            $paid = $order->exists ? $order->paidAmount() : 0;
            $order->subtotal = $total;
            $order->total_harga = $total;
            $order->status_pembayaran = $paid <= 0 ? 'belum_bayar' : ($paid >= $total ? 'lunas' : 'cicilan');
        });
    }
}
