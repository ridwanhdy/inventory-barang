<?php

namespace App\Models;

use App\Support\SalesStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderDetail extends Model
{
    protected $fillable = ['order_id', 'product_id', 'quantity', 'harga', 'subtotal'];
    protected $casts = ['quantity' => 'integer', 'harga' => 'integer', 'subtotal' => 'integer'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            $order = Order::query()->whereKey($this->order_id)->lockForUpdate()->first();
            if (! $order) {
                throw ValidationException::withMessages(['orderDetails' => 'Penjualan harus disimpan sebelum menambahkan barang.']);
            }
            $old = $this->exists ? static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail() : null;
            if ($old && $old->order_id != $this->order_id) {
                throw ValidationException::withMessages(['orderDetails' => 'Barang tidak dapat dipindahkan ke penjualan lain.']);
            }
            $rawQuantity = $this->getAttributes()['quantity'] ?? null;
            if (! preg_match('/^[0-9]+$/', (string) $rawQuantity) || (int) $rawQuantity < 1 || (int) $rawQuantity > 2147483647) {
                throw ValidationException::withMessages(['orderDetails' => 'Jumlah barang harus berupa bilangan bulat positif.']);
            }
            $changed = ! $old || $old->product_id != $this->product_id || $old->quantity !== $this->quantity || $old->harga !== $this->harga;
            if ($changed && ! $order->canEditItems()) {
                throw ValidationException::withMessages(['orderDetails' => 'Barang hanya dapat diubah saat penjualan masih diproses dan belum memiliki pembayaran.']);
            }
            if (! $old || $old->product_id != $this->product_id) {
                $product = Product::query()->find($this->product_id);
                if (! $product) {
                    throw ValidationException::withMessages(['orderDetails' => 'Pilih produk yang tersedia.']);
                }
                $this->harga = $product->harga_jual;
            } else {
                $this->harga = $old->harga;
            }
            $this->subtotal = $this->quantity * $this->harga;
            $otherTotal = (int) $order->orderDetails()->when($old, fn ($query) => $query->whereKeyNot($old->id))->sum('subtotal');
            if ($this->subtotal < 0 || $otherTotal + $this->subtotal > 2147483647) {
                throw ValidationException::withMessages(['orderDetails' => 'Nilai barang melebihi batas penjualan yang didukung.']);
            }
            $changes = [];
            if ($old) {
                $changes[$old->product_id] = $old->quantity;
            }
            $changes[$this->product_id] = ($changes[$this->product_id] ?? 0) - $this->quantity;
            SalesStock::change($changes);
            $saved = parent::save($options);
            if ($saved) {
                $order->refreshFinancials();
            }
            return $saved;
        }, 3);
    }

    public function delete()
    {
        if (! $this->exists) {
            return null;
        }
        return DB::transaction(function () {
            $order = Order::query()->whereKey($this->order_id)->lockForUpdate()->firstOrFail();
            if (! $order->canEditItems()) {
                throw ValidationException::withMessages(['orderDetails' => 'Barang penjualan yang sudah dibayar atau ditutup tidak dapat dihapus.']);
            }
            $detail = static::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();
            SalesStock::change([$detail->product_id => $detail->quantity]);
            $deleted = parent::delete();
            if ($deleted) {
                $order->refreshFinancials();
            }
            return $deleted;
        }, 3);
    }
}
