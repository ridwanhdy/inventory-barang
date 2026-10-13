<div class="space-y-4 p-4">
    <h3 class="font-semibold">Riwayat pembayaran</h3>
    @if ($canPay)
        <p class="text-sm text-gray-600">Gunakan tombol Bayar di bagian atas halaman untuk mencatat pembayaran pelanggan.</p>
    @endif
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Total penjualan:</span>
        <span class="font-bold">Rp{{ number_format($totalHarga, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Sudah dibayar:</span>
        <span class="font-bold">Rp{{ number_format($totalBayar, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Sisa tagihan:</span>
        <span class="font-bold">Rp{{ number_format($sisaBayar, 0, ',', '.') }}</span>
    </div>
</div>
