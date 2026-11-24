# Implementation Plan — POS & Inventory Operations Platform

**Dokumen Acuan:** [`POS_Skillage_Edupark_Design_Blueprint.md`](./POS_Skillage_Edupark_Design_Blueprint.md)  
**Target Direktori:** `/home/pak-hakim/Pak-Hakim/Project/Inventory-Management-Sietem`  
**Stack:** Laravel 13 (`13.43.0`) | PHP 8.5 | Livewire 4 + PowerGrid 7 | Tailwind CSS | Neon PostgreSQL 18  
**Arsitektur:** Modular Monolith | Single Warehouse (extensible) | Flexible Branding & Flexible Payment Gateway  

---

## 1. Status Baseline Saat Ini (Sudah Selesai)

Fondasi awal sudah terpasang dan terverifikasi empiris sebelum implementasi modul baru:

- [x] Clone repository `velora-1d/Inventory-Management-Sietem` ke `/home/pak-hakim/Pak-Hakim/Project/Inventory-Management-Sietem`
- [x] Upgrade framework ke **Laravel 13 (`13.43.0`)**, `laravel/tinker:^3.0`, `blade-ui-kit/blade-heroicons:^2.7`, `openspout/openspout:^5.0`, `power-components/livewire-powergrid:^7.0`, `fruitcake/laravel-debugbar:^4.0`, `phpunit/phpunit:^12.0`
- [x] Penyesuaian **Livewire PowerGrid v7** (`config/livewire-powergrid.php`, filter `'inline'`, `HtmlString` untuk field HTML, `POWERGRID_PARTIALS_ENABLED=false` di `phpunit.xml`, `resources/css/app.css`, `resources/js/app.js`, `tailwind.config.js`)
- [x] Kompatibilitas **PostgreSQL** (`config/database.php` default `pgsql`, penghapusan `SUBSTRING_INDEX` di migrasi username, `whereLike(..., caseSensitive: false)`, migrasi `cache` & `cache_locks`, konfigurasi `docker-compose.yml`)
- [x] Koneksi aktif ke **Neon PostgreSQL 18.7** (Project `Inventory` / `winter-silence-83325425`, database `neondb`), seluruh 20 migrasi & 8 seeder terpasang
- [x] **Flexible Payment Gateway** (`config/payment.php`, `app/Payments/PaymentGatewayManager.php`, `PaymentGatewayInterface`, driver `manual`, `pakasir`, `midtrans`, `xendit`, `custom`, webhook dinamis `/api/v1/webhooks/payment/{gateway}` & `/api/v1/callback/{gateway}`)
- [x] Test suite terverifikasi (**24 passed, 73 assertions**) dan `npm run build` lulus tanpa error

---

## 2. Arsitektur Flexible Branding (Ubah Nama & Logo via Pengaturan)

Seluruh identitas brand **tidak di-hardcode** di view maupun `.env`. Admin/Super Admin/Owner dapat mengubah nama brand, tagline, singkatan, nomor WA konfirmasi, dan logo kapan saja dari menu **Pengaturan (Settings)**.

### 2.1 Setting Keys (`settings` table)

| Setting Key | Default Value | Tipe Input di Settings | Penggunaan di Aplikasi |
|---|---|---|---|
| `brand_name` | `POS Skillage Edupark` | Text | `<title>`, header sidebar desktop & mobile, halaman login/register, footer, customer portal, judul tracking publik |
| `brand_subtitle` | `Business Operations Platform` | Text | Subjudul di halaman login, customer portal, header brand |
| `brand_short_code` | `PSE` | Text (max 6) | Kotak logo teks pada faktur cetak (`sales/print.blade.php`) & mode compact |
| `brand_logo` | `""` *(kosong → fallback SVG Blueprint)* | File Upload (`png/jpg/webp/svg`) | Logo custom di sidebar, auth page, dan faktur; otomatis fallback ke SVG `app-icon.svg` dari Blueprint bila kosong |
| `brand_whatsapp_admin` | `081234567890` | Text / Phone | Nomor tujuan tombol **"Konfirmasi Transfer via WhatsApp"** pada alur Customer Order |
| `store_name` | `TB. Kencana Pamijahan` *(sudah ada)* | Text | Nama toko/cabang pada struk & faktur |
| `store_address` | *(sudah ada)* | Textarea | Alamat toko pada faktur & halaman info kontak |
| `store_phone` | *(sudah ada)* | Text | Nomor telepon toko pada faktur |

### 2.2 File Target Flexible Branding

- `app/Helpers/BrandingHelper.php` (atau `app/Services/BrandingService.php`) — helper `brand_setting()` / `brand_name()` / `brand_logo_url()` yang membaca `Setting::get(...)` dengan fallback aman ke `config('app.name')` dan invalidasi cache otomatis saat update di `Setting::set(...)`.
- `database/seeders/SettingSeeder.php` — menambahkan default `brand_*` via `firstOrCreate()` (non-destruktif terhadap perubahan admin).
- `app/Livewire/Settings/SettingForm.php` & `resources/views/livewire/settings/setting-form.blade.php` — menambahkan `WithFileUploads` untuk upload `brand_logo` ke disk `public` (`storage/branding/...`) lengkap dengan preview & tombol reset ke SVG default.
- `app/Livewire/Settings/SettingTable.php` — menampilkan badge preview kecil untuk `brand_logo` & tetap menyamarkan key sensitif (`*_key`, `*_secret`, `*_token`).
- `resources/views/components/application-logo.blade.php` — merender `<img>` bila `brand_logo` terisi, atau SVG default `app-icon.svg` (`#142B4A` + `#2563EB` + `#10B981`) bila kosong.
- `resources/views/layouts/app.blade.php`, `resources/views/layouts/navigation.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/layouts/footer.blade.php`, `resources/views/auth/login.blade.php`, dan `resources/views/sales/print.blade.php` — mengganti `config('app.name')` & teks hardcode (`TB`) menjadi nilai dinamis dari Flexible Branding.

### 2.3 Design System Tokens (Blueprint §10 — Tanpa Warna Ungu)

| Token | HEX | Penggunaan |
|---|---|---|
| Primary / Navy | `#142B4A` | Sidebar header/accent, heading utama, identitas brand |
| Primary Blue | `#2563EB` | CTA utama, link aktif, focus ring |
| Success / Emerald | `#10B981` | Status selesai, pembayaran lunas, stok aman |
| Secondary / Teal | `#0F766E` | Aksen sekunder, badge kategori/informasi |
| Warning / Amber | `#D97706` | Pending, menunggu approval, stok menipis |
| Danger / Red | `#DC2626` | Error, overdue, pembatalan, stok habis |
| Surface / Card | `#FFFFFF` | Card, modal, panel data |
| Background | `#F8FAFC` | Latar utama aplikasi |
| Border | `#DCE3EA` | Garis pemisah & border tabel/card |
| Text / Muted | `#1F2937` / `#64748B` | Teks utama & teks sekunder |

> **Catatan Audit CSS:** Bersihkan sisa aksen ungu/indigo di `resources/views/layouts/navigation.blade.php` (`.icon-users`, `.icon-settings`) dan `resources/css/app.css` agar konsisten 100% mengikuti palet Navy/Blue/Emerald/Teal.

---

## 3. Roadmap Implementasi Lengkap (Fase 1 s/d Fase 8)

### Fase 1 — Foundation: Flexible Branding, Design System, RBAC & Audit Trail
- [x] **1.1 Flexible Branding:** Implementasi `BrandingHelper`/`BrandingService`, seeder key `brand_*`, upload `brand_logo` di `SettingForm`, pembaruan seluruh layout & faktur cetak.
- [x] **1.2 UI Design System Alignment:** Terapkan token warna `#142B4A`, `#2563EB`, `#10B981`, `#0F766E`, `#F8FAFC`, `#DCE3EA` pada `resources/css/app.css`, `tailwind.config.js`, `navigation.blade.php`, dan komponen UI inti.
- [x] **1.3 RBAC Granular (11 Role):**
  - Instal `spatie/laravel-permission` (tabel `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`).
  - Seed 11 role sesuai Blueprint §3: `owner`, `super_admin`, `admin`, `manager`, `purchasing`, `warehouse`, `qc_packing`, `cashier`, `finance`, `shipping`, `customer`.
  - Seed permission granular per modul (`pos.*`, `orders.*`, `inventory.*`, `procurement.*`, `work.*`, `shipping.*`, `finance.*`, `reports.*`, `settings.*`, `users.*`).
  - Tambahkan manajemen Role & Permission pada modul `Users` & `Settings`, serta proteksi route/controller/Livewire action (`@can` / middleware / Policy).
- [x] **1.4 Audit Trail & Idempotency:**
  - Migrasi tabel `audit_logs` (`actor_id`, `action`, `entity_type`, `entity_id`, `before`, `after`, `reason`, `ip_address`, `user_agent`, `created_at`) + `AuditService`.
  - Migrasi tabel `idempotency_keys` (`key`, `actor_id`, `route`, `request_hash`, `response_payload`, `status_code`, `expires_at`) untuk proteksi double-submit & webhook replay.
- [x] **Verifikasi Fase 1:** Test perubahan `brand_name` & `brand_logo` dari Settings terpantul di layout & struk; test permission denial (HTTP 403) untuk setiap role; `php artisan test` lulus 100%.

---

### Fase 2 — Core Inventory Ledger & POS Enhancement
- [x] **2.1 Database Inventory Ledger (Single Warehouse Extensible):**
  - Migrasi `warehouses` (default: `Main Warehouse`), `stock_balances` (`warehouse_id`, `product_id`, `on_hand`, `reserved`, `incoming`, constraint check non-negatif), `stock_movements` (immutable: `type` = `IN|OUT|RESERVE|RELEASE|ADJUSTMENT|RETURN_OUT|RETURN_IN`, `qty`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `reason`, `created_by`), `stock_reservations` (`order_id`, `product_id`, `warehouse_id`, `quantity`, `status`, `expires_at`), `stock_opnames` & `stock_opname_items`.
  - Migrasi backfill saldo awal dari `products.quantity` ke `stock_balances` & `stock_movements` awal agar data eksisting langsung sinkron.
- [x] **2.2 `InventoryLedgerService`:**
  - Operasi atomik memakai `DB::transaction` + `lockForUpdate()` pada `stock_balances`.
  - Rumus ketat: `available = on_hand - reserved`.
  - Sinkronisasi `products.quantity` dari `stock_balances.on_hand` (atau accessor kompatibel) agar modul POS/laporan lama tetap berfungsi mulus.
  - Refactor `SaleService` & `PurchaseService` untuk mencatat movement `OUT` / `IN` lewat `InventoryLedgerService`.
- [x] **2.3 UI Modul Inventory:**
  - Menu **Inventory** (Saldo Stok, Stock Movement Ledger, Reservasi Aktif, Stock Opname & Adjustment dengan alur approval Manager + wajib alasan di `audit_logs`).
- [x] **2.4 POS Enhancement:**
  - Tambah kolom `barcode` (indexed/unique nullable) pada `products` + dukungan scan barcode di halaman POS (`sales/create.blade.php`).
  - Migrasi `cash_registers`, `cash_shifts` (open/close shift kasir, saldo awal, realisasi kas akhir, selisih), dan `sale_payments` (mendukung split payment tunai + non-tunai).
- [x] **Verifikasi Fase 2:** Unit & feature tests untuk kalkulasi `available`, row-locking concurrency test (anti-oversell), immutability `stock_movements`, barcode lookup, dan tutup shift kasir.

---

### Fase 3 — Customer Order Portal & Checkout
- [x] **3.1 Database Customer Orders:**
  - Migrasi `customer_addresses`, `orders` (`order_number`, `public_tracking_token`, `customer_id`, `customer_address_id`, `status`, `payment_status`, `fulfillment_status`, `shipment_status`, `delivery_type` [`pickup|delivery`], `subtotal`, `discount_total`, `tax_total`, `shipping_cost`, `total`, `notes`), `order_items` (snapshot harga/HPP/diskon), `order_payments` (bukti transfer, metode, gateway, nominal, status, verified_by, verified_at), `order_status_histories`.
  - Gunakan tipe `numeric(15,2)` / `bigInteger` konsisten di PostgreSQL (hindari floating point).
- [x] **3.2 Customer Portal (`/shop` & `/my-orders`):**
  - Registrasi & login customer (role `customer`), manajemen alamat pengiriman (`customer_addresses`).
  - Katalog produk publik (menampilkan `available` stock), detail produk, cart (session/DB), halaman checkout (pilih pickup/delivery, alamat, metode bayar).
  - Server-side price & `available` stock validation → pembuatan `orders` (`Pending Payment`) → reservasi stok atomik (`stock_reservations` + `reserved` bertambah).
- [x] **3.3 Pembayaran Customer Order (Manual WA + Flexible Gateway):**
  - Default baseline: pelanggan memilih Transfer Manual → tampil instruksi rekening toko + tombol kirim konfirmasi ke WhatsApp admin (`brand_whatsapp_admin`) + upload bukti transfer → Admin/Finance memverifikasi pembayaran di dashboard (`order_payments`) → status order berubah ke `Confirmed` dan memicu event ke Work Management.
  - Jika gateway online (`pakasir` / `midtrans` / `xendit` / `custom`) diaktifkan, checkout otomatis memakai `PaymentGatewayManager`.
- [x] **3.4 Manajemen Order Admin & Anti-IDOR:**
  - Policy ownership ketat: customer hanya dapat melihat/membatalkan order miliknya sendiri.
  - Pembatalan order sebelum picking otomatis melepaskan `stock_reservations` dan mencatat `RELEASE` movement.
- [x] **Verifikasi Fase 3:** Feature tests registrasi customer, checkout validasi harga server-side, pelepasan reservasi saat cancel, verifikasi pembayaran admin, dan test anti-IDOR lintas customer.

---

### Fase 4 — Order Fulfillment & Work Management
- [x] **4.1 Database Work Management:**
  - Migrasi `order_workflows`, `workflow_steps` (urutan, departemen/role default, SLA menit, dependency step, flag `public_milestone`), `order_tasks` (`order_id`, `workflow_step_id`, `title`, `status` [`pending|assigned|in_progress|on_hold|review|revision|completed|cancelled`], `assigned_to`, `department`, `priority`, `due_at`, `started_at`, `completed_at`), `task_assignments` (history perubahan PIC), `task_dependencies`, `task_histories`, `task_comments`, `task_attachments` (private disk storage).
- [x] **4.2 Workflow & Assignment Engine:**
  - Saat order berstatus `Confirmed`, listener/use-case otomatis men-generate task dari workflow template (default: `Picking → QC → Packing → Ready to Ship`).
  - **Auto-Assignment:** memilih user aktif sesuai role/departemen dengan beban task aktif paling sedikit (round-robin/workload balance).
  - **Dependency Guard:** task tidak bisa dimulai sebelum dependency selesai; Manager dapat melakukan override / reassign PIC dengan wajib mengisi alasan (tersimpan di `task_assignments` & `audit_logs`).
  - **QC Revision Loop:** QC dapat mengembalikan task ke status `Revision` dengan catatan dan bukti lampiran.
- [x] **4.3 UI Work Management:**
  - Halaman **Task Board (Kanban)**, **My Tasks**, **All Tasks**, dan **Workflow Builder** (di Settings) dengan indikator SLA (On Track / Warning / Overdue).
- [x] **Verifikasi Fase 4:** Feature tests auto-generate task saat order confirmed, auto-assignment workload balancing, dependency blocking, QC revision loop, dan manager override audit log.

---

### Fase 5 — Shipping & Public Customer Tracking
- [x] **5.1 Database Shipping & Tracking:**
  - Migrasi `shipments` (`order_id`, `carrier`, `service`, `tracking_number`, `status`, `shipped_at`, `estimated_delivery_at`, `delivered_at`, `proof_of_delivery_path`, `confirmed_by`, `confirmation_source`), `shipment_events`, dan `order_tracking_events` (`order_id`, `milestone_code`, `title`, `description`, `is_public`, `occurred_at`).
- [x] **5.2 Alur Pengiriman & Penerimaan:**
  - Task `Packing` selesai → status order `Ready to Ship` → tim `Shipping` input kurir/layanan/resi → status `Shipped` + event tracking publik *"Pesanan dikirim"*.
  - Konfirmasi penerimaan dapat dilakukan oleh Customer atau oleh Admin berdasarkan upload bukti kurir (`proof_of_delivery_path`) → status `Delivered` → evaluasi aturan penutupan → `Completed`.
- [x] **5.3 Halaman Tracking Publik (`/track/{token}`):**
  - Akses menggunakan `public_tracking_token` acak (tanpa login atau dengan link dari detail order).
  - **Privasi Ketat (Blueprint §8):** hanya menampilkan milestone publik (*Pesanan diterima → Pembayaran dikonfirmasi → Pesanan sedang diproses → Pesanan dikemas → Pesanan dikirim → Pesanan diterima → Pesanan selesai*); **dilarang** menampilkan alamat lengkap, nomor HP penuh, catatan internal, atau nama PIC internal.
- [x] **Verifikasi Fase 5:** Feature tests transisi status pengiriman, public tracking privacy assertion (memastikan PII/internal task tidak bocor di response HTML/JSON), dan konfirmasi penerimaan.

---

### Fase 6 — Procurement & Purchase Order Lengkap
- [x] **6.1 Purchase Request (PR) & Approval:**
  - Migrasi `purchase_requests`, `purchase_request_items`, `purchase_request_approvals`.
  - Fitur pembuatan PR berdasarkan kebutuhan stok menipis (`on_hand <= min_stock`, cek `incoming`) → diajukan ke Manager → approval/rejection dengan catatan alasan.
- [x] **6.2 Purchase Order (PO) & Maker-Checker:**
  - Migrasi `purchase_orders`, `purchase_order_items`, `purchase_order_approvals`.
  - Status PO: `Draft → Pending Approval → Approved → Sent → Partially Received → Fully Received → Closed` (+ `Rejected`, `Cancelled`).
  - Segregation of duties: pembuat PO (`created_by`) tidak boleh menyetujui PO-nya sendiri; revisi PO yang sudah `Approved` memerlukan re-approval.
  - Saat PO `Approved`/`Sent`, saldo `incoming` pada `stock_balances` bertambah.
- [x] **6.3 Goods Receipt, 3-Way Match & Purchase Return:**
  - Migrasi `goods_receipts`, `goods_receipt_items` (penerimaan parsial maupun penuh + toleransi qty + QC penerimaan; hanya saat Goods Receipt diposting, `on_hand` bertambah, `incoming` berkurang, dan `stock_movements` `IN` tercatat).
  - Migrasi `supplier_invoices` (pencocokan 3 arah: PO vs Goods Receipt vs Invoice Supplier).
  - Migrasi `purchase_returns` & `purchase_return_items` (`RETURN_OUT` movement + klaim ke supplier).
- [x] **Verifikasi Fase 6:** Feature tests siklus penuh PR → PO approval (maker-checker) → partial Goods Receipt → `incoming` vs `on_hand` → Purchase Return.

---

### Fase 7 — Finance, Accounts Payable, Refunds & Reports
- [x] **7.1 Accounts Payable & Refunds:**
  - Migrasi `accounts_payable` (hutang supplier otomatis dari Supplier Invoice yang terverifikasi, jadwal jatuh tempo, pembayaran cicilan/lunas ke `finance_transactions`).
  - Migrasi `refunds` (pengajuan & pencatatan pengembalian dana untuk order batal/retur, status terpisah dari order & shipment).
- [x] **7.2 Modul Reports Lengkap:**
  - Laporan Penjualan (POS vs Customer Order), Laporan Mutasi & Valuasi Stok (`stock_movements`), Laporan Pembelian & Hutang Supplier, Laporan Produktivitas & SLA Task Tim, serta export XLSX/CSV (OpenSpout v5) & cetak PDF/Print.

---

### Fase 8 — Security Hardening, Concurrency & UAT Readiness
- [x] Rate limiting login/register/checkout/tracking publik & webhook throttling.
- [x] Validasi ketat upload file (MIME/size) + penyimpanan dokumen internal di disk `local`/`private` via signed controller download.
- [x] Pengujian menyeluruh: RBAC authorization matrix, IDOR cross-tenant/cross-customer, concurrency stock reservation (`lockForUpdate`), webhook idempotency & replay defense, backup/restore Neon PostgreSQL.
- [x] Pembaruan dokumentasi operasional & `00-AGY-Memory/Inventory-Management-Sietem/`.

---

## 4. Keputusan Bisnis yang Perlu Dikonfirmasi (Blueprint §15)

Sebelum memasuki Fase 3–6, keputusan kebijakan berikut perlu dikonfirmasi (default baseline sudah disiapkan):

| Topik | Opsi Baseline yang Disarankan | Status |
|---|---|---|
| **Branding Awal** | Default `POS Skillage Edupark` (ubah bebas dari Settings) | Siap dikonfirmasi di Fase 1 |
| **RBAC Engine** | `spatie/laravel-permission` (11 role & permission granular) | Siap dikonfirmasi di Fase 1 |
| **Customer Registration** | Public self-register + admin-created customer | Perlu konfirmasi sebelum Fase 3 |
| **Delivery Mode** | Pickup di toko + Delivery kurir aktif sejak awal | Perlu konfirmasi sebelum Fase 3 |
| **Task Scope** | Order fulfillment task dulu, lalu general operational task | Perlu konfirmasi sebelum Fase 4 |
| **Delivery Confirmation** | Customer konfirmasi sendiri + Admin fallback via bukti kurir | Perlu konfirmasi sebelum Fase 5 |
| **Returns Policy** | Retur POS & Customer Order (restock barang baik / catat rusak + refund) | Perlu detail sebelum Fase 7 |
| **Tax & Invoice** | Pajak/PPN konfigurabel di Settings & format nomor dokumen | Perlu detail sebelum Fase 3 |
