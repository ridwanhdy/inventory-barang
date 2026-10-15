@php
    $stockStatuses = $details->map(fn ($detail) => \App\Support\StockPresentation::status(
        $detail->stok,
        \App\Support\StockPresentation::minimum($detail),
    ));
    $availableCount = $stockStatuses->filter(fn ($status) => $status === 'Tersedia')->count();
    $attentionCount = $stockStatuses->filter(fn ($status) => in_array($status, ['Habis', 'Stok rendah', 'Perlu diperiksa'], true))->count();
    $unrecordedCount = $stockStatuses->filter(fn ($status) => $status === 'Belum tercatat')->count();
@endphp
<table class="summary">
    <tr>
        <td><div class="summary-label">Jumlah item</div><div class="summary-value">{{ number_format($details->count(), 0, ',', '.') }}</div></td>
        <td><div class="summary-label">Tersedia</div><div class="summary-value">{{ number_format($availableCount, 0, ',', '.') }}</div></td>
        <td><div class="summary-label">Perlu perhatian</div><div class="summary-value">{{ number_format($attentionCount, 0, ',', '.') }}</div></td>
        <td><div class="summary-label">Belum tercatat</div><div class="summary-value">{{ number_format($unrecordedCount, 0, ',', '.') }}</div></td>
    </tr>
</table>
