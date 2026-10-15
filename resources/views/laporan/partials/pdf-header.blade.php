@php
    $displayPeriod = $periodeLabel ?? null;
    if (blank($displayPeriod)) {
        $start = filled($tanggalAwal ?? null) ? \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') : null;
        $end = filled($tanggalAkhir ?? null) ? \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') : null;
        $displayPeriod = match (true) {
            $start !== null && $end !== null => $start === $end ? $start : $start . ' - ' . $end,
            $start !== null => 'Mulai ' . $start,
            $end !== null => 'Sampai ' . $end,
            default => 'Semua periode',
        };
    }
    $printedAt = now('Asia/Jakarta')->format('d/m/Y H:i:s') . ' WIB';
@endphp
<div class="footer">
    <table><tr>
        <td>Mandiri Konveksi &middot; {{ $reportTitle }}</td>
        <td class="text-right">Dicetak: {{ $printedAt }} &middot; Halaman <span class="page-number"></span></td>
    </tr></table>
</div>
<div class="company-header">
    <h1 class="company-name">MANDIRI KONVEKSI</h1>
    <p class="company-address">Jl. K.H. Abdul Wakid RT.15/RW.03 Dukuh Banaran, Desa, Banaran, Kerik, Kec. Takeran, Kabupaten Magetan, Jawa Timur 63383</p>
    <p class="company-phone">Telepon: 0821-4362-9650</p>
</div>
<h2 class="report-title">{{ $reportTitle }}</h2>
<table class="metadata">
    <tr><td class="label">Periode</td><td>: {{ $displayPeriod }}</td></tr>
    <tr><td class="label">Filter</td><td>: {{ filled($filterLabel ?? null) ? $filterLabel : 'Data terpilih' }}</td></tr>
</table>
