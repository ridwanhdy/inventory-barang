<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->no_transaksi ?? '#' . $order->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .invoice-info {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total {
            text-align: right;
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUKTI PENJUALAN</h1>
        <h2>Mandiri Konveksi</h2>
        <p>Jl. K.H. Abdul Wakid RT.15/RW.03 Dukuh Banaran, Desa, Banaran, Kerik, Kec. Takeran, Kabupaten Magetan, Jawa Timur 63383</p>
        <p>Telp: 0821-4362-9650</p>
    </div>

    <div class="invoice-info">
        <p><strong>No. Transaksi:</strong> {{ $order->no_transaksi ?? '#' . $order->id }}</p>
        <p><strong>Tanggal:</strong> {{ $order->tanggal_order->format('d/m/Y') }}</p>
        <p><strong>Status penjualan:</strong> {{ match ($order->status_transaksi) {
            'selesai' => 'Barang diserahkan',
            'batal' => 'Dibatalkan',
            default => 'Barang disiapkan',
        } }}</p>
        <p><strong>Status pembayaran:</strong> {{ match ($order->status_pembayaran) {
            'lunas' => 'Lunas',
            'cicilan' => 'Dibayar sebagian',
            default => 'Belum dibayar',
        } }}</p>
    </div>

    <div class="company-info">
        <h3>Informasi pelanggan</h3>
        <p><strong>Nama:</strong> {{ $order->nama_customer }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderDetails as $detail)
            <tr>
                <td>
                    {{ $detail->product?->nama_product ?? 'Produk tidak tersedia' }}
                    @if ($detail->product)
                        <br><small>{{ $detail->product->warna }} · Ukuran {{ $detail->product->ukuran }}</small>
                    @endif
                </td>
                <td>{{ $detail->quantity }}</td>
                <td>Rp{{ number_format($detail->harga, 0, ',', '.') }}</td>
                <td>Rp{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">Total penjualan:</td>
                <td>Rp{{ number_format($totalHarga, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Pembayaran diterima:</td>
                <td>Rp{{ number_format($totalBayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Sisa pembayaran:</td>
                <td>Rp{{ number_format($sisaBayar, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="total">Kembalian:</td>
                <td>Rp{{ number_format($totalKembalian, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Terima kasih atas pembelian Anda!</p>
        @if ($order->status_transaksi === 'batal')
            <p>Penjualan dibatalkan. Bukti ini disimpan sebagai riwayat transaksi.</p>
        @endif
    </div>
</body>
</html>
