# Inventory Barang

Sistem inventory & produksi untuk usaha konveksi, dengan data demo khusus kaos polos.
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

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@admin.com` | `password` |
| Kasir | `kasir@kasir.com` | `kasir` |

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

Pilih **Jenis periode** pada laporan: semua periode, rentang tanggal, rentang bulan (dapat melintasi tahun), atau rentang tahun. Isi batas awal dan akhir, lalu klik **Terapkan filter**. Tombol **Cetak PDF** juga menerapkan pilihan periode terbaru dan mencetak seluruh hasil sesuai pencarian, filter, serta urutan tabel. Cetak per baris atau beberapa baris melalui **Cetak PDF Terpilih** tetap tersedia.

Laporan penjualan hanya memuat transaksi selesai dan menampilkan jumlah transaksi serta total penjualan. Pada laporan stok, periode mengacu pada **pembaruan stok terakhir dalam WIB**; jumlah yang dicetak adalah saldo stok saat ini, bukan saldo historis pada tanggal tersebut. PDF mencantumkan periode, filter, ringkasan, dan waktu cetak. Rentang terbalik atau belum lengkap ditolak; hasil kosong menampilkan pemberitahuan sebelum mencetak.

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

`php artisan migrate --seed` mengisi data demo **30 hari terakhir, termasuk hari saat seeder dijalankan, dalam WIB**. Contoh dijalankan pada 15 Oktober 2026: periode 16 September–15 Oktober 2026.

- Akun admin dan kasir sesuai tabel di atas.
- 3 satuan, 5 kategori, dan 3 pemasok dummy.
- **1.152 varian kaos polos**: seluruh kombinasi 9 ukuran, 16 warna, dan 8 jenis kain. Setiap varian memiliki harga dan stok sendiri.
- Ukuran: XS, S, M, L, XL, XXL, 3XL, 4XL, 5XL.
- Warna: Hitam, Putih, Navy, Abu-abu Misty, Abu-abu Tua, Merah, Maroon, Biru Royal, Biru Muda, Hijau Botol, Hijau Army, Kuning, Oranye, Cokelat, Beige, Ungu.
- Kain: Cotton Combed 20s/24s/30s/40s, Cotton Carded 24s, CVC, TC, dan Cotton Bamboo.
- 148 bahan baku: kain per warna, rib sesuai warna, benang, label ukuran, label merek, dan plastik kemasan.
- Pembelian dan produksi setiap hari, termasuk contoh rencana, proses berjalan, hasil baik/rusak, pengembalian sisa bahan, dan pembatalan.
- 120 penjualan beserta rincian barang dan pembayaran: tunai, kembalian, transfer, cicilan, lunas, belum dibayar, dan pembatalan.
- Riwayat pergerakan bahan baku yang mengikuti penerimaan, pemakaian, dan pengembalian bahan.

Stok awal nol; saldo akhir berasal dari pembelian → pemakaian bahan → hasil produksi → penjualan. Data ini juga mengisi dashboard, laporan stok, dan laporan penjualan. Foto produk memakai gambar pengganti bawaan aplikasi.

Menjalankan ulang `php artisan db:seed` pada periode yang sama tidak menggandakan transaksi dummy atau menambah stok lagi. Seeder memperbarui akun demo dan master data serta mempertahankan data lain yang sudah ada.

Untuk **mengganti seluruh isi database lama** dengan dataset baru, cadangkan database terlebih dahulu, lalu jalankan:

```bash
php artisan migrate:fresh --seed
```

Perintah tersebut menghapus seluruh tabel dan data sebelum membuat ulang database. Gunakan `php artisan db:seed` bila data lama perlu dipertahankan.

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
