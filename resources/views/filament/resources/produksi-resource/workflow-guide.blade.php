<div class="production-guide">
    <style>
        .production-guide { padding: 1rem 1.25rem; background: var(--inv-surface-raised); border: 1px solid var(--inv-border); border-radius: .75rem; color: var(--inv-ink); }
        .production-guide ol { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; margin-top: .75rem; }
        .production-guide li { list-style: none; }
        .production-guide p { margin-top: .25rem; font-size: .8rem; line-height: 1.5; color: var(--inv-muted); }
        .production-guide strong { font-size: .85rem; }
        @media (max-width: 640px) { .production-guide ol { grid-template-columns: 1fr; gap: .65rem; } }
    </style>
    <strong>Alur produksi dan perubahan stok</strong>
    <ol aria-label="Tahapan produksi">
        <li><strong>1. Rencana</strong><p>Pilih produk, target, dan kebutuhan bahan. Stok belum berubah.</p></li>
        <li><strong>2. Sedang diproduksi</strong><p>Mulai produksi: bahan diambil dan stok bahan berkurang. Catat tambahan bahan bila diperlukan.</p></li>
        <li><strong>3. Selesai</strong><p>Catat hasil layak jual, barang rusak, dan sisa bahan. Stok produk bertambah sesuai hasil layak jual.</p></li>
    </ol>
</div>
