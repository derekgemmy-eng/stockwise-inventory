# StockWise — Inventory Management Website

StockWise adalah website manajemen inventaris berbasis **PHP + MySQL** yang dibungkus dengan **Docker Compose**, sehingga bisa dijalankan di komputer mana pun tanpa menginstal PHP atau MySQL secara manual.

**Fitur utama (dipakai untuk verifikasi):** menambah produk dan mengubah stok dengan tombol `+` / `−`, lengkap dengan dashboard otomatis.

## Fitur

- Tambah, lihat, ubah, dan hapus produk (CRUD).
- Cari berdasarkan nama produk, SKU, atau kategori.
- Filter produk: stok menipis atau stok aman.
- Tambah/kurangi stok dengan tombol; stok tidak bisa kurang dari nol.
- Status otomatis: Aman, Menipis, atau Habis berdasarkan batas minimum.
- Dashboard: jumlah produk, total unit, jumlah stok menipis, dan nilai persediaan.
- Validasi SKU unik dan validasi input dasar di sisi server.

## Prasyarat

- Docker Desktop (atau Docker Engine) dengan Docker Compose v2.
- Git.
- Versi yang diuji: **Docker `ISI_VERSI`**, **Docker Compose `ISI_VERSI`**, Windows 11.
  (Cek dengan `docker version` dan `docker compose version`.)
- Port **8080** di komputer harus kosong.

## Cara menjalankan

### 1. Clone repository

```bash
git clone https://github.com/derekgemmy-eng/stockwise-inventory.git
cd stockwise-inventory
```

### 2. Siapkan file `.env`

Salin file contoh menjadi file `.env`:

```bash
# Windows (Command Prompt / PowerShell)
copy .env.example .env

# macOS / Linux
cp .env.example .env
```

Variabel yang tersedia (lihat `.env.example`, tanpa rahasia asli):

| Variabel | Fungsi |
|---|---|
| `DB_NAME` | Nama database |
| `DB_USER` | Pengguna database untuk aplikasi |
| `DB_PASSWORD` | Kata sandi pengguna database |
| `DB_ROOT_PASSWORD` | Kata sandi root MySQL |

Nilai bawaan sudah aman untuk percobaan lokal. Jangan commit file `.env`.

### 3. Build dan jalankan

```bash
docker compose up --build -d
```

Build pertama bisa memakan beberapa menit karena mengunduh image PHP dan MySQL. Compose otomatis menunggu MySQL siap sebelum menjalankan website.

(Opsional) Build image saja dengan tag tugas:

```bash
docker build -t group12/stockwise:week6 .
```

### 4. Buka aplikasi

| Info | Nilai |
|---|---|
| URL | http://localhost:8080 |
| Port di komputer (host) | 8080 |
| Port di container | 80 |
| Layanan | `web` (PHP 8.3 + Apache), `db` (MySQL 8.4) |

## Cara memastikan aplikasi bekerja

1. Jalankan `docker compose ps` — `web` harus **Up** dan `db` harus **healthy**.
2. Buka http://localhost:8080 — halaman *Inventory overview* tampil dengan 4 produk demo.
   Dashboard awal: **4 jenis produk, 64 total unit, 2 stok menipis, nilai persediaan Rp 4.806.000**.
3. Tambah produk: Nama `Test Product`, SKU `TEST-001`, Kategori `Elektronik`, Stok `10`, Harga `50000`, Batas minimum `5`, lalu simpan.
   Hasil: produk muncul di daftar dan dashboard menjadi **5 produk, 74 unit**.
4. Tekan `+` pada `TEST-001`. Hasil: stok menjadi 11 dan total unit menjadi 75.
5. Tambah produk lagi dengan SKU `TEST-001`. Hasil: muncul pesan bahwa SKU sudah digunakan.
6. Tekan `−` pada produk yang stoknya 0. Hasil: stok tidak turun di bawah nol.
7. Hapus `TEST-001` agar data kembali bersih.

## Melihat log dan status

```bash
docker compose ps
docker compose logs --tail=100 web db
docker compose logs -f web
```

## Menghentikan dan membersihkan

```bash
# Hentikan dan hapus container (data database tetap tersimpan)
docker compose down

# Hapus juga data database (DESTRUKTIF — data hilang)
docker compose down -v
```

## Troubleshooting

| Masalah | Penyebab dan solusi |
|---|---|
| `port is already allocated` / port 8080 dipakai | Ubah `"8080:80"` di `compose.yaml` menjadi `"8081:80"`, lalu buka http://localhost:8081. |
| Halaman menampilkan "Koneksi database gagal" | Database belum siap. Tunggu sekitar 30 detik, cek `docker compose logs db`, lalu muat ulang halaman. |
| Produk demo tidak muncul | `database/init.sql` hanya berjalan saat volume database baru dibuat. Jalankan `docker compose down -v` lalu `docker compose up --build -d`. |
| Perintah `docker` tidak dikenali | Pastikan Docker Desktop sudah dibuka dan statusnya *running*. |
| Perubahan kode tidak terlihat | Jalankan `docker compose up --build -d` agar image dibangun ulang. |
| Variabel env tidak terbaca | Pastikan file `.env` ada di folder yang sama dengan `compose.yaml` dan nama variabelnya sesuai `.env.example`. |

## Keterbatasan yang diketahui

- Data demo hanya dibuat saat volume database baru diinisialisasi.
- Log Apache menampilkan `408` (koneksi cadangan browser yang tidak dipakai) dan `404 /favicon.ico` (aplikasi tidak punya ikon tab). Keduanya tidak memengaruhi fungsi aplikasi.
- Tidak ada fitur login; aplikasi ditujukan untuk penggunaan lokal.
- Password bawaan hanya untuk pengembangan lokal, bukan untuk produksi.
