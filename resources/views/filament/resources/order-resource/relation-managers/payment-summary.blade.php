<div class="space-y-4">
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Total Harga:</span>
        <span class="font-bold">Rp {{ number_format($totalHarga, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Total Bayar:</span>
        <span class="font-bold">Rp {{ number_format($totalBayar, 0, ',', '.') }}</span>
    </div>
    
    <div class="inv-payment-summary-card flex justify-between items-center p-4 rounded-lg">
        <span class="font-medium">Sisa Bayar:</span>
        <span class="font-bold">Rp {{ number_format($sisaBayar, 0, ',', '.') }}</span>
    </div>
</div>
