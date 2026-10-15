<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    @include('laporan.partials.pdf-styles')
</head>
<body>
    @include('laporan.partials.pdf-header', ['reportTitle' => 'Laporan Penjualan'])

    @php
        $jumlahTransaksi = $orders->count();
        $rataRataPenjualan = $jumlahTransaksi > 0 ? $totalPenjualan / $jumlahTransaksi : 0;
    @endphp
    <table class="summary">
        <tr>
            <td><div class="summary-label">Jumlah transaksi</div><div class="summary-value">{{ number_format($jumlahTransaksi, 0, ',', '.') }}</div></td>
            <td><div class="summary-label">Total penjualan</div><div class="summary-value">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</div></td>
            <td><div class="summary-label">Rata-rata per transaksi</div><div class="summary-value">Rp {{ number_format($rataRataPenjualan, 0, ',', '.') }}</div></td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 11%;">Tanggal</th>
                <th style="width: 22%;">Nomor penjualan</th>
                <th style="width: 24%;">Pelanggan</th>
                <th class="text-right" style="width: 21%;">Total penjualan</th>
                <th style="width: 18%;">Status pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="text-center">{{ $loop->iteration }}</td>
                    <td>{{ $order->tanggal_order ? \Carbon\Carbon::parse($order->tanggal_order)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $order->no_transaksi ?? '#' . $order->id }}</td>
                    <td>{{ $order->nama_customer ?: '-' }}</td>
                    <td class="text-right money">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $order->status_pembayaran ?? '-')) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">Tidak ada data penjualan untuk periode dan filter yang dipilih.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right">Total penjualan</td>
                <td class="text-right money">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</td>
                <td>{{ number_format($jumlahTransaksi, 0, ',', '.') }} transaksi</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
