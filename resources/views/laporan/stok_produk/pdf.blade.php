<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Produk</title>
    @include('laporan.partials.pdf-styles')
</head>
<body>
    @include('laporan.partials.pdf-header', ['reportTitle' => 'Laporan Stok Produk'])
    @include('laporan.partials.pdf-stock-summary')

    <p class="notice">Stok saat ini; periode menyaring waktu pembaruan stok, bukan saldo historis.<br>Jumlah item menunjukkan jumlah catatan stok. Status stok rendah mengikuti batas minimum yang telah diatur.</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 29%;">Nama produk / varian</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 10%;">Satuan</th>
                <th class="text-right" style="width: 9%;">Stok</th>
                <th style="width: 16%;">Status stok</th>
                <th style="width: 17%;">Diperbarui (WIB)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($details as $detail)
                @php
                    $stok = $detail->stok;
                    $minimum = \App\Support\StockPresentation::minimum($detail);
                    $status = \App\Support\StockPresentation::status($stok, $minimum);
                    $color = \App\Support\StockPresentation::color($stok, $minimum);
                    $varian = collect([
                        filled($detail->product?->warna) ? 'Warna: ' . $detail->product->warna : null,
                        filled($detail->product?->ukuran) ? 'Ukuran: ' . $detail->product->ukuran : null,
                    ])->filter()->implode(' / ');
                @endphp
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $detail->product->nama_product ?? '-' }}</strong>
                        @if($varian)<div class="muted variant">{{ $varian }}</div>@endif
                    </td>
                    <td>{{ $detail->product->kategori->nama_kategori ?? '-' }}</td>
                    <td>{{ $detail->product->satuan->nama_satuan ?? '-' }}</td>
                    <td class="text-right">{{ $stok === null ? '-' : rtrim(rtrim(number_format((float) $stok, 2, ',', '.'), '0'), ',') }}</td>
                    <td><span class="status status-{{ $color }}">{{ $status }}</span></td>
                    <td>{{ $detail->updated_at ? $detail->updated_at->copy()->timezone('Asia/Jakarta')->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty-state">Tidak ada data stok produk untuk periode dan filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
