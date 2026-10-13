<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Produksi extends Model
{
    public const DRAFT = 'Draft';

    public const PROCESS = 'Proses';

    public const COMPLETED = 'Selesai';

    public const CANCELLED = 'Batal';

    protected $fillable = [
        'product_id', 'jumlah_produksi', 'produksi_mulai', 'catatan', 'status',
        'produksi_selesai', 'jumlah_hasil', 'jumlah_rusak', 'alasan_batal', 'tanggal_batal',
        'produksi_dimulai_at', 'hasil_dari_data_lama', 'catatan_hasil',
    ];

    protected $attributes = ['status' => self::DRAFT, 'hasil_dari_data_lama' => false];

    protected $casts = [
        'jumlah_produksi' => 'integer',
        'jumlah_hasil' => 'integer',
        'jumlah_rusak' => 'integer',
        'produksi_mulai' => 'date',
        'produksi_selesai' => 'date',
        'tanggal_batal' => 'date',
        'produksi_dimulai_at' => 'datetime',
        'hasil_dari_data_lama' => 'boolean',
    ];

    public static function statusLabels(): array
    {
        return [self::DRAFT => 'Rencana', self::PROCESS => 'Sedang diproduksi', self::COMPLETED => 'Selesai', self::CANCELLED => 'Dibatalkan'];
    }

    public function isDraft(): bool
    {
        return $this->status === self::DRAFT;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::PROCESS;
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::COMPLETED, self::CANCELLED], true);
    }

    public function hasStarted(): bool
    {
        return $this->produksi_dimulai_at !== null;
    }

    public function getReferenceAttribute(): string
    {
        return 'PRD-'.str_pad((string) $this->getKey(), 6, '0', STR_PAD_LEFT);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function produksiDetail()
    {
        return $this->hasMany(ProduksiDetail::class);
    }

    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $current = static::query()->lockForUpdate()->findOrFail($this->getKey());
                if (! $current->isDraft()) {
                    throw ValidationException::withMessages(['status' => 'Produksi yang sudah dimulai tidak dapat diubah. Gunakan tindakan Selesaikan produksi atau Batalkan produksi.']);
                }
                if ($this->isDirty(['status', 'produksi_selesai', 'jumlah_hasil', 'jumlah_rusak', 'alasan_batal', 'tanggal_batal', 'produksi_dimulai_at', 'hasil_dari_data_lama', 'catatan_hasil'])) {
                    throw ValidationException::withMessages(['status' => 'Perubahan tahap produksi harus melalui tindakan Mulai, Selesaikan, atau Batalkan produksi.']);
                }
            } elseif ($this->status !== self::DRAFT || $this->produksi_selesai !== null || $this->jumlah_hasil !== null || $this->jumlah_rusak !== null || $this->alasan_batal !== null || $this->tanggal_batal !== null || $this->hasStarted() || $this->hasil_dari_data_lama || $this->catatan_hasil !== null) {
                throw ValidationException::withMessages(['status' => 'Produksi baru harus dibuat sebagai Rencana sebelum bahan dikeluarkan dan produksi dimulai.']);
            }

            Validator::make($this->getAttributes(), [
                'product_id' => ['required', 'integer', 'exists:products,id'],
                'jumlah_produksi' => ['required', 'integer', 'min:1', 'max:2147483647'],
                'produksi_mulai' => ['required', 'date'],
                'catatan' => ['nullable', 'string'],
            ], [
                'jumlah_produksi.integer' => 'Target produksi harus berupa jumlah unit bulat.',
                'jumlah_produksi.min' => 'Target produksi harus lebih dari nol.',
                'product_id.exists' => 'Produk yang dipilih tidak tersedia.',
                'produksi_mulai.date' => 'Tanggal rencana produksi tidak valid.',
            ])->validate();

            return parent::save($options);
        }, 3);
    }

    public function delete()
    {
        return DB::transaction(function () {
            if (! $this->exists) {
                return parent::delete();
            }
            $current = static::query()->lockForUpdate()->findOrFail($this->getKey());
            if (! $current->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Hanya rencana produksi yang belum dimulai dapat dihapus. Riwayat bahan dan hasil produksi harus tetap tersimpan.']);
            }

            return parent::delete();
        }, 3);
    }
}
