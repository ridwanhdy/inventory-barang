<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Bahan Baku</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #eee; }
        h2 { margin-bottom: 0; }
        .badge-danger { color: #fff; background: #dc2626; padding: 2px 6px; border-radius: 4px; }
        .badge-warning { color: #fff; background: #f59e42; padding: 2px 6px; border-radius: 4px; }
        .badge-success { color: #fff; background: #16a34a; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h2>Laporan Stok Bahan Baku</h2>
    @if(isset($tanggalAwal) && isset($tanggalAkhir))
        <p>Periode: {{ date('d/m/Y', strtotime($tanggalAwal)) }} - {{ date('d/m/Y', strtotime($tanggalAkhir)) }}</p>
    @endif
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Bahan</th>
                <th>Kategori</th>
                <th>Satuan</th>
                <th>Stok</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $i => $detail)
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td>{{ $detail->bahanBaku->nama_bahan ?? '-' }}</td>
                    <td>{{ $detail->bahanBaku->kategori->nama_kategori ?? '-' }}</td>
                    <td>{{ $detail->bahanBaku->satuan->nama_satuan ?? '-' }}</td>
                    <td>
                        @php
                            $stok = $detail->stok;
                            $badge = $stok < 5 ? 'badge-danger' : ($stok < 10 ? 'badge-warning' : 'badge-success');
                        @endphp
                        <span class="{{ $badge }}">{{ $stok }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html> 