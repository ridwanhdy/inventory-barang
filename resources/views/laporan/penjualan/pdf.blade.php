<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
            padding: 0;
        }
        .company-address {
            font-size: 12px;
            margin: 5px 0;
        }
        .company-phone {
            font-size: 12px;
            margin: 5px 0;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        .report-period {
            font-size: 12px;
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .print-date {
            text-align: right;
            margin-top: 20px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="company-name">MANDIRI KONVEKSI</h1>
        <p class="company-address">Jl. K.H. Abdul Wakid RT.15/RW.03 Dukuh Banaran, Desa, Banaran, Kerik, Kec. Takeran, Kabupaten Magetan, Jawa Timur 63383</p>
        <p class="company-phone">Phone: 0821-4362-9650</p>
    </div>

    <div class="report-title">Laporan Penjualan</div>
    <div class="report-period">Periode: {{ date('d/m/Y', strtotime($tanggalAwal)) }} - {{ date('d/m/Y', strtotime($tanggalAkhir)) }}</div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Order</th>
                <th>Customer</th>
                <th>Total Penjualan</th>
                <th>Status Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $order)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ date('d/m/Y', strtotime($order->tanggal_order)) }}</td>
                <td>{{ $order->id }}</td>
                <td>{{ $order->customer->nama }}</td>
                <td class="text-right">Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $order->status_pembayaran)) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center;">Tidak ada data penjualan</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">Total Penjualan:</td>
                <td colspan="2" class="text-right">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="print-date">
        Dicetak pada: {{ date('d/m/Y H:i:s') }}
    </div>
</body>
</html> 