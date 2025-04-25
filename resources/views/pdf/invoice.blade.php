<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $order->id }}</title>
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
        <h1>INVOICE</h1>
        <h2>Mandiri Konveksi</h2>
        <p>Jl. K.H. Abdul Wakid RT.15/RW.03 Dukuh Banaran, Desa, Banaran, Kerik, Kec. Takeran, Kabupaten Magetan, Jawa Timur 63383</p>
        <p>Telp: 0821-4362-9650</p>
    </div>

    <div class="invoice-info">
        <p><strong>Invoice #:</strong> {{ $order->id }}</p>
        <p><strong>Tanggal:</strong> {{ $order->tanggal_order->format('d/m/Y') }}</p>
        <p><strong>Status Pembayaran:</strong> {{ ucfirst($order->status_pembayaran) }}</p>
    </div>

    <div class="company-info">
        <h3>Informasi Customer</h3>
        <p><strong>Nama:</strong> {{ $customer->nama }}</p>
        <p><strong>Alamat:</strong> {{ $customer->alamat }}</p>
        <p><strong>No. Telp:</strong> {{ $customer->no_telp }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produk</th>
                <th>Quantity</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderDetails as $detail)
            <tr>
                <td>{{ $detail->product->nama_product }}</td>
                <td>{{ $detail->quantity }}</td>
                <td>Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="total">Total Bayar:</td>
                <td>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Terima kasih atas pembelian Anda!</p>
        <p>Barang yang sudah dibeli tidak dapat ditukar/dikembalikan</p>
    </div>
</body>
</html> 