# Inventory Barang

Sistem inventory & produksi untuk usaha manufaktur (konveksi kaos & jaket).  
Mengelola alur dari pembelian bahan baku → produksi → penjualan → pembayaran.

**Stack:** Laravel 12 · Filament 3 · MySQL · DomPDF

---

## Fitur

| Modul | Keterangan |
|-------|------------|
| **Master Data** | User, satuan, kategori, pemasok, bahan baku, produk |
| **Pembelian** | Beli bahan baku dari pemasok → stok bahan naik |
| **Produksi** | Pakai bahan baku → hasilkan barang jadi |
| **Order** | Penjualan produk ke customer |
| **Payment** | Catat pembayaran (cash / bank, cicilan) |
| **Stok** | Pantau stok bahan baku & produk jadi |
| **Laporan** | Laporan stok & penjualan (export PDF) |

---

## Konsep Stok

Master data **tidak** menyimpan stok. Stok dipantau di tabel detail.

```
BahanBaku   → identitas bahan (nama, satuan, kategori)
BahanBakuDetail → stok bahan baku

Product     → identitas produk (nama, harga, ukuran, warna, foto)
ProductDetail   → stok barang jadi
```

### Alur stok

```
[Pembelian]  → stok BAHAN BAKU naik
     ↓
[Produksi]   → stok BAHAN BAKU turun
             → stok BARANG JADI naik
     ↓
[Order]      → stok BARANG JADI turun
```

---

## Requirement

- PHP 8.2+
- Composer
- MySQL
- Node.js & npm (opsional, untuk Vite)

---

## Instalasi

```bash
# 1. Clone & masuk folder
git clone https://github.com/ridwanhdy/inventory-barang.git
cd inventory-barang

# 2. Install dependency
composer install

# 3. Environment
copy .env.example .env
php artisan key:generate

# 4. Sesuaikan database di .env
# DB_DATABASE=inventory_barang
# DB_USERNAME=root
# DB_PASSWORD=

# 5. Migrasi + seeder
php artisan migrate --seed

# 6. Jalankan
php artisan serve
```

Akses: [http://localhost:8000/admin](http://localhost:8000/admin)

| | |
|---|---|
| **Email** | `admin@admin.com` |
| **Password** | `password` |

---

## Modul Admin (Filament)

### Master
- Satuan, Kategori, Pemasok
- Bahan Baku (+ detail stok)
- Produk (+ detail stok)
- User

### Transaksi
- Pembelian bahan baku
- Produksi (+ detail bahan dipakai)
- Order / penjualan
- Payment

### Laporan
- Laporan stok bahan baku
- Laporan stok produk
- Laporan penjualan

---

## Struktur Database (inti)

```
satuans, kategoris, pemasoks, users
        ↓
bahan_bakus ──→ bahan_baku_details (stok)
products     ──→ product_details (stok)
        ↓
pembelians ──→ pembelian_details
produksis  ──→ produksi_details
orders     ──→ order_details
           ──→ payments
```

---

## Seeder

`php artisan migrate --seed` mengisi:

- 1 admin
- Satuan, kategori, pemasok
- Produk kaos & jaket + stok awal
- Bahan baku (kain, benang, resleting, dll) + stok awal
- Sample pembelian & produksi

---

## Alur Kerja Singkat

1. **Input master** — satuan, kategori, pemasok, bahan baku, produk
2. **Beli bahan** — buat pembelian → stok bahan naik otomatis
3. **Produksi** — buat rencana dan kebutuhan bahan → mulai produksi → catat hasil sebenarnya dan sisa bahan → selesai
4. **Jual** — buat order + detail produk → stok produk turun
5. **Bayar** — catat payment hingga lunas
6. **Lapor** — cetak PDF stok / penjualan

---

## Alur Produksi

Setiap batch memiliki nomor produksi dan satu varian produk (warna dan ukuran). Target rencana dipisahkan dari hasil sebenarnya.

1. **Rencana:** tentukan produk, target, rencana tanggal mulai, dan total kebutuhan bahan untuk satu batch. Menyimpan rencana belum mengurangi stok bahan.
2. **Mulai produksi:** periksa bahan dan catat tanggal mulai sebenarnya. Sistem memeriksa stok seluruh bahan, lalu mengurangi stok secara bersamaan. Bila satu bahan tidak cukup, produksi tetap berupa rencana dan seluruh stok tetap.
3. **Sedang diproduksi:** catat pengambilan bahan tambahan bila diperlukan. Untuk bahan yang sama, isi jumlah tambahan saja; sistem menggabungkannya ke jumlah bahan yang telah diambil.
4. **Catat hasil dan selesaikan:** isi hasil layak jual, barang rusak/tidak layak jual, tanggal selesai sebenarnya, dan jumlah sisa bahan yang benar-benar dikembalikan. Hanya hasil layak jual menambah stok produk. Jelaskan pada catatan hasil bila total hasil berbeda dari target.
5. **Pembatalan:** isi alasan pembatalan. Rencana yang belum dimulai tidak memengaruhi stok. Untuk produksi berjalan, hanya sisa bahan yang dicatat sebagai pengembalian yang kembali ke stok; bahan terpakai tetap tercatat.

Pemakaian akhir bahan = bahan diambil − bahan dikembalikan. Produksi selesai atau dibatalkan menjadi riwayat tetap dan tidak dapat diubah, dimulai ulang, atau dihapus dari halaman produksi.

Migrasi produksi mempertahankan stok lama. Jumlah hasil produksi selesai pada data lama mengikuti jumlah produksi yang tersimpan dan diberi keterangan sebagai data lama; jumlah barang rusak tidak diasumsikan nol.

## Tech Package

| Package | Fungsi |
|---------|--------|
| `filament/filament` 3.3 | Admin panel |
| `barryvdh/laravel-dompdf` | Export PDF |
| `charlieuki/receiptprinter` | Cetak struk |

---

## Lisensi

Project Tugas Akhir — penggunaan akademik.
