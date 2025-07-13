<?php

namespace App\Observers;

use App\Models\BahanBaku;
use App\Models\BahanBakuDetail;

class BahanBakuObserver
{
    /**
     * Handle the BahanBaku "created" event.
     */
    public function created(BahanBaku $bahanBaku): void
    {
        BahanBakuDetail::create([
            'bahan_baku_id' => $bahanBaku->id,
            'stok' => 0,
        ]);
    }

    /**
     * Handle the BahanBaku "updated" event.
     */
    public function updated(BahanBaku $bahanBaku): void
    {
        //
    }

    /**
     * Handle the BahanBaku "deleted" event.
     */
    public function deleted(BahanBaku $bahanBaku): void
    {
        $bahanBaku->bahanBakuDetails()->delete();
    }

    /**
     * Handle the BahanBaku "restored" event.
     */
    public function restored(BahanBaku $bahanBaku): void
    {
        //
    }

    /**
     * Handle the BahanBaku "force deleted" event.
     */
    public function forceDeleted(BahanBaku $bahanBaku): void
    {
        //
    }
}
