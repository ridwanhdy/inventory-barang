<div class="space-y-4 p-4">
    <h3 class="font-semibold">Riwayat pembayaran</h3>
    @if ($canPay)
        <p class="text-sm text-gray-600">Gunakan tombol Bayar di bagian atas halaman untuk mencatat pembayaran pelanggan.</p>
    @endif
    <div class="inv-payment-summary-card flex items-center gap-4 p-4 rounded-lg">
        <span class="font-medium shrink-0" style="width: min(45%, 9rem);">Total penjualan:</span>
        <span class="min-w-0 break-words text-left font-bold">Rp{{ number_format($totalHarga, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex items-center gap-4 p-4 rounded-lg">
        <span class="font-medium shrink-0" style="width: min(45%, 9rem);">Sudah dibayar:</span>
        <span class="min-w-0 break-words text-left font-bold">Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex items-center gap-4 p-4 rounded-lg">
        <span class="font-medium shrink-0" style="width: min(45%, 9rem);">Sisa tagihan:</span>
        <span class="min-w-0 break-words text-left font-bold">Rp{{ number_format($sisaBayar, 0, ',', '.') }}</span>
    </div>
</div>
