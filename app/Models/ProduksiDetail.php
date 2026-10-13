<?php

namespace App\Models;

use App\Support\ProductionWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProduksiDetail extends Model
{
    protected $fillable = ['produksi_id', 'bahan_baku_id', 'jumlah_digunakan', 'jumlah_dikembalikan'];

    protected $attributes = ['jumlah_dikembalikan' => 0];

    protected $casts = ['jumlah_digunakan' => 'decimal:2', 'jumlah_dikembalikan' => 'decimal:2'];

    public function produksi()
    {
        return $this->belongsTo(Produksi::class);
    }

    public function bahanBaku()
    {
        return $this->belongsTo(BahanBaku::class, 'bahan_baku_id');
    }

    public function getJumlahTerpakaiAttribute(): float
    {
        if (! $this->produksi?->hasStarted()) {
            return 0.0;
        }

        return round((float) $this->jumlah_digunakan - (float) $this->jumlah_dikembalikan, 2);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            $productionId = $this->exists ? $this->getRawOriginal('produksi_id') : $this->produksi_id;
            $production = Produksi::query()->lockForUpdate()->find($productionId);
            if (! $production) {
                throw ValidationException::withMessages(['produksiDetail' => 'Produksi tidak tersedia. Muat ulang halaman lalu coba kembali.']);
            }
            if ($production->isClosed() || ($this->exists && ! $production->isDraft())) {
                throw ValidationException::withMessages(['produksiDetail' => 'Bahan yang sudah dikeluarkan tidak dapat diubah atau dihapus. Catat sisa bahan saat menyelesaikan atau membatalkan produksi.']);
            }
            if ($this->exists) {
                static::query()->lockForUpdate()->findOrFail($this->getKey());
                if ($this->isDirty('produksi_id')) {
                    throw ValidationException::withMessages(['produksiDetail' => 'Bahan tidak dapat dipindahkan ke produksi lain.']);
                }
            }

            Validator::make($this->getAttributes(), [
                'bahan_baku_id' => ['required', 'integer', 'exists:bahan_bakus,id'],
                'jumlah_digunakan' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999.99'],
                'jumlah_dikembalikan' => ['required', 'numeric', 'in:0,0.00'],
            ], [
                'jumlah_digunakan.gt' => 'Jumlah bahan harus lebih dari nol.',
                'jumlah_digunakan.decimal' => 'Jumlah bahan dapat menggunakan maksimal dua angka desimal.',
                'bahan_baku_id.exists' => 'Bahan baku yang dipilih tidak tersedia.',
                'jumlah_dikembalikan.in' => 'Sisa bahan hanya dapat dikembalikan melalui tindakan Selesaikan atau Batalkan produksi.',
            ])->validate();
            if ($this->exists && $this->isDirty('jumlah_dikembalikan')) {
                throw ValidationException::withMessages(['produksiDetail' => 'Sisa bahan hanya dapat dikembalikan melalui tindakan Selesaikan atau Batalkan produksi.']);
            }

            $duplicate = static::query()->where('produksi_id', $productionId)->where('bahan_baku_id', $this->bahan_baku_id)
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['produksiDetail' => 'Bahan baku ini sudah dicatat pada produksi. Setiap bahan hanya boleh memiliki satu baris.']);
            }

            if (! $this->exists && $production->isProcessing()) {
                ProductionWorkflow::changeMaterialStock([(int) $this->bahan_baku_id => -(float) $this->jumlah_digunakan]);
            }

            return parent::save($options);
        }, 3);
    }

    public function delete()
    {
        return DB::transaction(function () {
            if (! $this->exists) {
                return parent::delete();
            }
            $production = Produksi::query()->lockForUpdate()->find($this->getRawOriginal('produksi_id'));
            if (! $production || ! $production->isDraft()) {
                throw ValidationException::withMessages(['produksiDetail' => 'Bahan hanya dapat dihapus saat produksi masih berupa Rencana.']);
            }
            static::query()->lockForUpdate()->findOrFail($this->getKey());

            return parent::delete();
        }, 3);
    }
}
