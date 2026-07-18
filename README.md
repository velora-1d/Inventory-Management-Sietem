# 📦 Inventory Management System (Sistem Manajemen Inventaris)

<div align="center">

![Velora Banner](https://img.shields.io/badge/Project_by-Velora-purple?style=for-the-badge)
![Developer](https://img.shields.io/badge/Developer-Mahin_Utsman_Nawawi%2C_S.H.-blue?style=for-the-badge)
![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red?style=for-the-badge&logo=laravel)
![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-777BB4?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)

</div>

---

Sistem Manajemen Inventaris (Inventory Management System) adalah aplikasi berbasis web enterprise-grade yang dirancang untuk mengelola stok barang, transaksi penjualan (POS), pembelian ke supplier, pencatatan arus kas keuangan (buku kas), serta laporan analitik secara real-time. 

Proyek ini dibangun secara khusus untuk **Velora** dan dikembangkan sepenuhnya oleh **Mahin Utsman Nawawi, S.H.** menggunakan teknologi modern Laravel 12 dan TALL Stack (Tailwind, Alpine, Laravel, Livewire).

---

## 👥 Profil Proyek

*   **Pemilik / Publisher:** **Velora**
*   **Lead Developer:** **Mahin Utsman Nawawi, S.H.**
*   **Tujuan:** Menyediakan sistem kasir (POS) dan pengelolaan gudang yang modern, cepat, akurat, dan mudah digunakan untuk mendukung efisiensi operasional bisnis.

---

## 🌟 Daftar Modul & Menu Sidebar

Berikut adalah daftar menu utama yang terdapat pada sidebar aplikasi beserta fungsi dan rute teknisnya:

| Grup Sidebar | Menu Utama | Deskripsi Fungsi | Rute Teknis (`route()`) |
| :--- | :--- | :--- | :--- |
| **Utama** | 📊 Dashboard | Menampilkan ringkasan analitik keuangan real-time (Penjualan, Arus Kas Bersih, Profit) serta grafik tren penjualan harian/bulanan (ApexCharts) dan peringatan stok menipis. | `dashboard` |
| **Transaksi** | 💳 POS (Kasir) | Antarmuka kasir yang responsif untuk transaksi penjualan cepat. Mendukung diskon global, kalkulasi kembalian otomatis, struk belanja, dan state keranjang belanja persisten. | `sales.create` |
| | 🧾 Daftar Penjualan | Log riwayat seluruh transaksi penjualan, detail pesanan, opsi cetak ulang invoice, dan pencarian transaksi. | `sales.index` |
| | 🛍️ Daftar Pembelian | Manajemen Purchase Order (PO) ke pemasok dan pencatatan penerimaan barang untuk memperbarui stok fisik gudang. | `purchases.index` |
| **Kontak & Rekan**| 👥 Pelanggan (Customers) | Basis data kontak pelanggan untuk pelacakan transaksi penjualan. | `customers.index` |
| | 🏢 Pemasok (Suppliers) | Rekaman data supplier guna mempermudah pemesanan stok barang. | `suppliers.index` |
| **Katalog Produk** | 📦 Produk Barang | Manajemen detail barang, stok produk, harga beli/jual, margin profit, serta unggahan gambar produk. | `products.index` |
| | 🏷️ Kategori Produk | Pengelompokan produk secara terstruktur agar pencarian dan laporan lebih efisien. | `categories.index` |
| | ⚖️ Satuan Barang | Konfigurasi ukuran kuantitas produk (misalnya: Pcs, Kilogram, Pack, Liter, Box). | `units.index` |
| **Keuangan** | 💵 Transaksi Keuangan | Pencatatan pemasukan dan pengeluaran kas manual di luar transaksi penjualan/pembelian otomatis. | `finance.transactions.index`|
| | 📂 Kategori Transaksi | Penggolongan jenis pemasukan dan pengeluaran keuangan untuk mempermudah analisis laba/rugi. | `finance.categories.index` |
| **Sistem & Akses**| 👤 Pengguna (Users) | Manajemen hak akses pengguna sistem (administrator, kasir, staff gudang). | `users.index` |
| | ⚙️ Pengaturan | Konfigurasi profil toko serta format mata uang dinamis (posisi simbol, pemisah ribuan, pemisah desimal, presisi angka) secara instan dan global. | `settings.index` |

---

## 🛠️ Stack Teknologi

Sistem ini didesain menggunakan teknologi terkini untuk menjamin performa dan skalabilitas tinggi:

*   **Backend Framework:** Laravel 12.x
*   **Frontend Reactivity:** Livewire 3 + Alpine.js
*   **UI & CSS Framework:** Tailwind CSS (Desain modern terinspirasi oleh Shadcn UI)
*   **Tabel Data:** Livewire PowerGrid (Dilengkapi pencarian instan dan AJAX filter)
*   **Visualisasi Data:** ApexCharts
*   **Paket Icon:** Blade Heroicons
*   **Database:** SQLite (default) / MySQL

---

## 🚀 Panduan Instalasi & Setup

Ikuti langkah-langkah di bawah ini untuk memasang proyek ini di komputer lokal Anda.

### Prasyarat Sebelum Instalasi
Pastikan sistem Anda telah terpasang:
*   PHP >= 8.2
*   Composer
*   Node.js (versi LTS direkomendasikan) & NPM
*   Database (SQLite / MySQL)

---

### Langkah 1: Kloning Repositori
Kloning repositori proyek ini ke komputer Anda:
```bash
git clone https://github.com/fajarghifar/inventory-management-system.git
cd inventory-management-system
```

### Langkah 2: Cara Instalasi Otomatis (Direkomendasikan)
Proyek ini menyediakan script setup otomatis di `composer.json` yang akan melakukan instalasi dependensi PHP & JS, menyalin konfigurasi `.env`, membuat kunci aplikasi, dan menjalankan migrasi database:
```bash
composer run setup
```

---

### Langkah Manual (Alternatif)
Jika Anda ingin melakukan konfigurasi langkah demi langkah secara manual, silakan ikuti petunjuk berikut:

1. **Pasang Dependensi PHP:**
   ```bash
   composer install
   ```

2. **Salin File Konfigurasi Environment:**
   ```bash
   cp .env.example .env
   ```

3. **Buat Application Key:**
   ```bash
   php artisan key:generate
   ```

4. **Konfigurasi Database:**
   Buka file `.env` di editor teks Anda. Jika Anda ingin menggunakan SQLite (default), pastikan konfigurasinya seperti berikut:
   ```env
   DB_CONNECTION=sqlite
   ```
   *Catatan: Pastikan file `database/database.sqlite` sudah dibuat atau biarkan aplikasi membuatnya saat migrasi.*

   Jika Anda ingin menggunakan MySQL, ubah konfigurasinya menjadi:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nama_database_anda
   DB_USERNAME=username_mysql_anda
   DB_PASSWORD=password_mysql_anda
   ```

5. **Jalankan Migrasi Database dan Seed Data:**
   Perintah ini akan membuat semua tabel database dan mengisi data contoh bawaan (produk, kategori, mata uang, dan akun admin):
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Buat Symbolic Link untuk Media / Upload:**
   ```bash
   php artisan storage:link
   ```

7. **Pasang Dependensi Frontend & Compile Asset:**
   ```bash
   npm install
   npm run build
   ```

---

### Langkah 3: Menjalankan Server Pengembangan

Setelah semua terinstal, jalankan server pengembangan menggunakan perintah bawaan:
```bash
composer run dev
```
*Perintah di atas akan menjalankan Laravel Server (serve), Vite compiler, Queue listener, dan Log listener secara bersamaan menggunakan paket `concurrently`.*

Akses sistem di browser Anda melalui alamat: **`http://localhost:8000`** atau **`http://127.0.0.1:8000`**.

---

## 🔑 Informasi Akun Demo / Default

Gunakan akun berikut untuk masuk ke dashboard sistem pertama kali setelah menjalankan seeder:

*   **Username / Email:** `admin` (atau `admin@admin.com`)
*   **Password:** `password`

---

## 📝 Lisensi
Proyek ini didistribusikan di bawah lisensi **MIT License**. Lihat file [LICENSE](LICENSE) untuk informasi lebih lanjut.

---

<div align="center">
  <p>Dibuat dengan ❤️ untuk <b>Velora</b> oleh <b>Mahin Utsman Nawawi, S.H.</b></p>
</div>
