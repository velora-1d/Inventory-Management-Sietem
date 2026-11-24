<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="480" viewBox="0 0 640 180" class="s1" style="max-width:100%;height:auto">
<rect width="640" height="180" rx="22" fill="#142B4A"/>
<g transform="translate(28 28)">
<rect x="0" y="0" width="124" height="124" rx="26" fill="#2563EB"/>
<path d="M28 37h68v14H42v15h47v14H42v19H28z" fill="#fff"/>
<path d="M72 78h24v21H72z" fill="#10B981"/>
<path d="M82 26h14v34H82z" fill="#10B981"/>
</g>
<text x="180" y="78" font-family="Inter,Arial,sans-serif" font-size="31" font-weight="700" fill="#fff">POS SKILLAGE</text>
<text x="180" y="116" font-family="Inter,Arial,sans-serif" font-size="25" font-weight="500" fill="#DCEAFE">EDUPARK</text>
<text x="180" y="145" font-family="Inter,Arial,sans-serif" font-size="13" letter-spacing="2" fill="#34D399">BUSINESS OPERATIONS PLATFORM</text>
</svg>
</p>

# POS Skillage Edupark

**SYSTEM DESIGN BLUEPRINT**  
Architecture • Business Workflow • Database • UI Design System • SVG Assets

**Technology baseline**  
Laravel 13 | Livewire | Tailwind CSS | PostgreSQL

**Architecture**  
Modular Monolith | Single Warehouse | Customer Order & POS

Dokumen acuan untuk pengembangan produk. Rancangan ini mengunci keputusan yang sudah disepakati dan menandai bagian yang masih perlu dikonfigurasi saat implementasi.

_Versi dokumen: 1.0 \| Status: Proposed baseline \| Tanggal: 23 November 2026_

---

## Daftar Isi

- [1. Ringkasan Produk](#1-ringkasan-produk)
- [2. Arsitektur Sistem](#2-arsitektur-sistem)
- [3. Role dan Permission](#3-role-dan-permission)
- [4. POS dan Customer Order](#4-pos-dan-customer-order)
- [5. Inventory & Stock Control](#5-inventory-stock-control)
- [6. Procurement & Purchase Order](#6-procurement-purchase-order)
- [7. Order Fulfillment & Work Management](#7-order-fulfillment-work-management)
- [8. Customer Tracking & Shipping](#8-customer-tracking-shipping)
- [9. Rancangan Database Inti](#9-rancangan-database-inti)
- [10. UI/UX Design System](#10-uiux-design-system)
- [11. Struktur Menu Aplikasi](#11-struktur-menu-aplikasi)
- [12. Business Rules & Kontrol Integritas](#12-business-rules-kontrol-integritas)
- [13. Tahapan Implementasi](#13-tahapan-implementasi)
- [14. Acceptance Criteria Utama](#14-acceptance-criteria-utama)
- [15. Keputusan yang Masih Perlu Difinalkan](#15-keputusan-yang-masih-perlu-difinalkan)
- [Lampiran: Aset SVG](#lampiran-aset-svg)

---

# 1. Ringkasan Produk

**POS Skillage Edupark** adalah aplikasi pengelolaan operasional bisnis yang mengintegrasikan transaksi kasir, pemesanan pelanggan, persediaan, pembelian, pekerjaan internal, pengiriman, dan keuangan dalam satu sumber data.

Sasaran utama sistem adalah memastikan setiap transaksi dan aktivitas operasional dapat ditelusuri dari awal sampai akhir: customer membuat order, stok dialokasikan, tim menjalankan pekerjaan, barang dikirim, dan penyelesaian transaksi tercatat.

| Area | Keputusan desain |
|---|---|
| Nama produk | POS Skillage Edupark |
| Backend | Laravel 13 |
| UI application | Livewire + Tailwind CSS |
| Database | PostgreSQL |
| Arsitektur | Modular monolith; satu aplikasi dan satu database relasional |
| Gudang | Single warehouse pada fase awal; model data tidak mengunci ekspansi |
| Customer Order | Customer dapat mendaftar, melihat katalog, checkout, dan tracking |
| Work Management | Penugasan otomatis + pengalihan manual oleh admin/manager |
| PO Approval | Purchase Order wajib melalui approval sesuai kebijakan |
| Customer tracking | Customer melihat milestone publik, bukan detail internal task |

## Prinsip desain

1. Satu sumber kebenaran untuk stok dan transaksi.
2. Setiap mutasi stok memiliki jejak audit.
3. Status order, task, pembayaran, dan shipment dipisahkan.
4. Approval dan perubahan penting harus dapat ditelusuri.
5. Fitur baru tidak boleh merusak alur POS dan inventory yang sudah berjalan.

---

# 2. Arsitektur Sistem

Gunakan modular monolith untuk menjaga kecepatan pengembangan dan konsistensi transaksi. Setiap domain memiliki batas tanggung jawab yang jelas, tetapi tidak perlu dipisah menjadi microservices pada fase awal.

<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="720" viewBox="0 0 1000 650" class="s2" style="max-width:100%;height:auto">
<defs><style>.s2 .box{stroke:#cbd5e1;stroke-width:2;rx:14}.s2 .h{font:bold 21px Inter,Arial;fill:#142b4a}.s2 .p{font:15px Inter,Arial;fill:#475569}.s2 .arr{stroke:#64748b;stroke-width:3;fill:none;marker-end:url(#m2)}</style>
<marker id="m2" markerWidth="10" markerHeight="10" refX="7" refY="3" orient="auto"><path d="M0 0L8 3L0 6" fill="none" stroke="#64748b" stroke-width="1.5"/></marker></defs>
<rect width="1000" height="650" fill="#fff"/>
<text x="500" y="42" text-anchor="middle" class="h" font-size="27">SYSTEM ARCHITECTURE</text>
<rect class="box" x="110" y="75" width="780" height="75" fill="#eff6ff"/><text class="h" x="500" y="107" text-anchor="middle">Presentation Layer</text><text class="p" x="500" y="132" text-anchor="middle">Customer Portal • POS • Admin Dashboard • Employee Workspace</text>
<path class="arr" d="M500 150V185"/>
<rect class="box" x="110" y="185" width="780" height="75" fill="#ecfdf5"/><text class="h" x="500" y="217" text-anchor="middle">Application Core — Laravel 13 + Livewire</text><text class="p" x="500" y="242" text-anchor="middle">Authorization • Validation • Use Cases • Events • Notifications</text>
<path class="arr" d="M500 260V295"/>
<rect class="box" x="65" y="295" width="870" height="145" fill="#f8fafc"/>
<text class="h" x="500" y="327" text-anchor="middle">Domain Modules</text>
<g class="p" text-anchor="middle">
<text x="170" y="365">POS</text><text x="340" y="365">Orders</text><text x="510" y="365">Inventory</text><text x="680" y="365">Procurement</text><text x="835" y="365">Finance</text>
<text x="170" y="405">Work Tasks</text><text x="340" y="405">Shipping</text><text x="510" y="405">Catalog</text><text x="680" y="405">Customers</text><text x="835" y="405">Reports</text>
</g>
<path class="arr" d="M500 440V475"/>
<rect class="box" x="110" y="475" width="780" height="65" fill="#eff6ff"/><text class="h" x="500" y="514" text-anchor="middle">PostgreSQL — Transactional Data, Stock Ledger &amp; Audit Trail</text>
<path class="arr" d="M500 540V575"/>
<rect class="box" x="110" y="575" width="780" height="50" fill="#ecfdf5"/><text class="p" x="500" y="606" text-anchor="middle">External Integrations: Payment Gateway • Messaging • Courier APIs (future)</text>
</svg>
</p>

<p align="center"><sub>Gambar 1. Arsitektur sistem tingkat tinggi (system-architecture.svg)</sub></p>

## Domain modules

| Modul | Tanggung jawab |
|---|---|
| Identity & Access | Autentikasi, role, permission, user, customer account |
| Product Catalog | Produk, kategori, SKU, barcode, unit, harga, gambar |
| POS | Cart kasir, penjualan, diskon, pembayaran, struk, shift |
| Customer Order | Katalog publik, cart, checkout, order, status, retur/refund |
| Inventory | Saldo stok, reservasi, mutasi, stock opname, adjustment |
| Procurement | Purchase request, approval, PO, supplier, penerimaan, retur |
| Work Management | Workflow template, task, assignment, SLA, komentar, lampiran |
| Shipping | Shipment, kurir, resi, event pengiriman, bukti serah terima |
| Finance | Kas/bank, pembayaran, refund, tagihan supplier, hutang |
| Reporting & Audit | Dashboard, laporan, audit log, export |

## Batas arsitektur

Controller/Livewire component menangani interaksi. Application services/use cases mengorkestrasi proses bisnis. Domain rules menjaga validasi transaksi. Repository/query layer digunakan bila kompleksitas query membutuhkannya. Hindari menaruh aturan stok, approval, atau pembayaran langsung di komponen UI.

---

# 3. Role dan Permission

| Role | Tanggung jawab / akses |
|---|---|
| Owner | Ringkasan bisnis, laporan, konfigurasi kebijakan, monitoring seluruh modul |
| Super Admin | Konfigurasi teknis, user, role, permission, master settings |
| Admin | Operasional order, customer, produk, dan koordinasi pekerjaan |
| Manager | Approval, pengawasan SLA, pengalihan PIC, override dengan alasan |
| Purchasing | Supplier, purchase request, pembuatan PO, penerimaan invoice supplier |
| Warehouse | Picking, stock movement, goods receiving, stock opname |
| QC / Packing | Task pemeriksaan kualitas dan pengemasan |
| Cashier | POS, pembayaran kasir, shift, cetak struk |
| Finance | Verifikasi pembayaran, refund, kas/bank, hutang supplier |
| Shipping | Persiapan pengiriman, resi, status dan bukti pengiriman |
| Customer | Katalog, cart, checkout, riwayat order, tracking publik |

## Aturan keamanan akses

- Terapkan permission granular, bukan hanya pemeriksaan nama role.
- Customer hanya boleh mengakses order miliknya sendiri (ownership check).
- Purchasing tidak boleh menyetujui PO yang dibuat sendiri bila kebijakan segregation of duties diaktifkan.
- Manager override harus meminta alasan dan mencatat aktor, waktu, nilai sebelum/sesudah.
- Jangan menerima user_id, harga, diskon, status pembayaran, atau total transaksi dari browser sebagai data tepercaya tanpa validasi server.

---

# 4. POS dan Customer Order

POS dan Customer Order memakai katalog produk serta sumber stok yang sama, tetapi memiliki alur transaksi berbeda.

## 4.1 POS / Kasir

**1.** Kasir memilih produk / scan barcode

↓

**2.** Cart + validasi harga dan ketersediaan

↓

**3.** Diskon / pajak sesuai konfigurasi

↓

**4.** Pembayaran: tunai / non-tunai / split (jika diaktifkan)

↓

**5.** Konfirmasi transaksi + invoice/struk

↓

**6.** Commit stock movement OUT + catat payment

## 4.2 Customer Order

**1.** Customer register/login

↓

**2.** Katalog → detail produk → cart

↓

**3.** Checkout: alamat, pengiriman, metode bayar

↓

**4.** Server validasi harga dan available stock

↓

**5.** Buat order + reservasi stok

↓

**6.** Customer transfer manual + konfirmasi ke WhatsApp admin; admin verifikasi pembayaran

↓

**7.** Generate fulfillment tasks

↓

**8.** Picking → QC → packing → shipment

↓

**9.** Delivered + konfirmasi → Completed

## Status order yang disarankan

| Status | Makna / transisi |
|---|---|
| Pending Payment | Order dibuat; menunggu transfer manual dan konfirmasi WhatsApp admin |
| Confirmed | Order lolos validasi dan siap diproses |
| Processing | Fulfillment sedang berjalan |
| Ready to Ship | Paket siap diserahkan |
| Shipped | Paket sudah diserahkan ke kurir |
| Delivered | Bukti/status penerimaan tersedia |
| Completed | Order ditutup setelah syarat penyelesaian terpenuhi |
| Cancelled | Order dibatalkan; reservasi dilepas sesuai aturan |
| Refunded | Refund diproses/selesai; bukan pengganti status shipment |

> Pembayaran, fulfillment, shipment, dan order memiliki status terpisah. Jangan membuat satu kolom status besar untuk menampung semua kondisi tersebut.

---

# 5. Inventory & Stock Control

Inventory menjadi sumber kebenaran kuantitas barang. Semua perubahan stok harus menghasilkan stock movement yang tidak dihapus atau ditimpa secara diam-diam.

## Konsep saldo

| Field | Definisi |
|---|---|
| on_hand | Jumlah fisik yang tercatat berada di gudang |
| reserved | Jumlah yang dialokasikan untuk order dan belum dikeluarkan |
| available | Stok yang dapat dijanjikan untuk order baru |
| incoming | Jumlah PO yang belum diterima; informasi perencanaan, bukan stok tersedia |

> **Rumus:** available = on_hand − reserved. Incoming tidak ditambahkan ke available sampai barang diterima dan goods receipt diposting.

## Peristiwa stok

| Event | Dampak |
|---|---|
| POS sale posted | on_hand berkurang; movement OUT |
| Customer order confirmed | reserved bertambah; on_hand tetap |
| Order cancelled sebelum picking | reserved berkurang |
| Picking diposting / barang keluar | on_hand dan reserved berkurang; movement OUT |
| Goods receipt posted | on_hand bertambah; movement IN |
| Stock opname adjustment | movement ADJUSTMENT dengan alasan dan approval |
| Purchase return posted | on_hand berkurang; movement RETURN_OUT |

## Kontrol teknis

Gunakan database transaction dan row-level locking saat reservasi atau posting mutasi untuk mencegah overselling. Sediakan idempotency key untuk aksi yang dapat terkirim ulang. Movement yang sudah diposting tidak diedit; koreksi dilakukan melalui reversal/adjustment baru.

---

# 6. Procurement & Purchase Order

**1.** Purchase Request (PR) dibuat

↓

**2.** Review kebutuhan + cek stok / incoming

↓

**3.** Approval Manager

↓

**4.** Purchase Order dibuat dari PR yang disetujui

↓

**5.** PO dikirim ke supplier

↓

**6.** Goods Receipt: penerimaan penuh / parsial

↓

**7.** QC penerimaan + posting stock IN

↓

**8.** Supplier invoice → Accounts Payable → Payment

## Entitas dan aturan PO

| Entitas | Aturan utama |
|---|---|
| Purchase Request | Mencatat kebutuhan, pemohon, alasan, item, kuantitas, estimasi biaya |
| PR Approval | Menyimpan keputusan, approver, waktu, komentar, dan alasan penolakan |
| Purchase Order | Nomor unik, supplier, harga, pajak/biaya, tanggal, termin, status |
| PO Approval | Wajib sebelum PO diterbitkan/dikirim; maker-checker dapat diterapkan |
| Goods Receipt | Mendukung penerimaan parsial; qty diterima tidak boleh melebihi toleransi tanpa approval |
| Supplier Invoice | Dapat dicocokkan dengan PO dan goods receipt (3-way match) |
| Purchase Return | Mengurangi stok melalui movement RETURN_OUT dan mencatat klaim supplier |

## Status PO

Draft → Pending Approval → Approved → Sent → Partially Received → Fully Received → Closed. Status Rejected dan Cancelled tersedia dengan alasan. PO yang sudah disetujui tidak boleh diubah diam-diam; perubahan material memerlukan revisi dan approval ulang.

---

# 7. Order Fulfillment & Work Management

Work Management menangani pekerjaan yang dibangkitkan dari order dan dapat diperluas untuk tugas operasional lain. Workflow dapat dikonfigurasi per jenis order.

**1.** Order confirmed

↓

**2.** Create tasks from workflow template

↓

**3.** Auto-assign by department / workload rule

↓

**4.** PIC starts task; update progress and evidence

↓

**5.** Dependencies unlock next task

↓

**6.** QC review / revision loop when needed

↓

**7.** Packing completed → shipment can be prepared

↓

**8.** Delivery confirmed → completion rules evaluated

## Status task

| Status | Definisi |
|---|---|
| Pending | Task dibuat, belum ditugaskan/dikerjakan |
| Assigned | PIC aktif sudah ditentukan |
| In Progress | PIC mulai mengerjakan |
| On Hold | Dihentikan sementara dengan alasan |
| Review | Menunggu pemeriksaan/approval |
| Revision | Perlu perbaikan; dikembalikan ke PIC |
| Completed | Kriteria task terpenuhi |
| Cancelled | Task dibatalkan dengan alasan dan otorisasi |

## Auto assignment dan manual reassignment

Aturan default: pilih karyawan aktif pada departemen yang ditentukan, perhitungkan kapasitas/beban task aktif, lalu gunakan pemerataan tugas. Manager dapat mengganti PIC, departemen, prioritas, atau deadline sesuai permission. Simpan assignment history agar jejak tanggung jawab tidak hilang.

## Task dependency

Contoh: Packing bergantung pada Picking selesai dan QC lulus. Shipment preparation bergantung pada Packing selesai. Dependency harus mencegah task dimulai terlalu awal. Override hanya untuk manager berwenang dan wajib menyimpan alasan.

---

# 8. Customer Tracking & Shipping

Customer tracking adalah tampilan publik yang mengambil event terpilih. Task internal tidak otomatis ditampilkan ke customer.

| Internal event | Customer milestone yang ditampilkan |
|---|---|
| Order verified | Pesanan diterima |
| Payment verified | Pembayaran dikonfirmasi |
| Picking / QC / Packing | Pesanan sedang diproses |
| Packing completed | Pesanan dikemas |
| Shipment handed over | Pesanan dikirim |
| Delivery proof accepted | Pesanan diterima |
| Order closure | Pesanan selesai |

## Data shipment

Simpan carrier/courier, service, tracking number, shipped_at, estimated delivery (jika tersedia), delivered_at, proof of delivery, dan shipment events. Fase awal dapat memakai input manual. Integrasi API kurir menjadi pengembangan lanjutan.

## Bukti penerimaan

Rekomendasi baseline: customer dapat mengonfirmasi penerimaan; admin dapat mengonfirmasi berdasarkan bukti kurir. Bila ada integrasi kurir, event delivered dari kurir menjadi sinyal, tetapi aturan penutupan tetap dapat memerlukan verifikasi. Semua konfirmasi harus memiliki aktor/sumber dan timestamp.

## Privasi

Gunakan public tracking token acak yang tidak mudah ditebak. Jangan menampilkan alamat lengkap, nomor telepon penuh, catatan internal, atau identitas PIC internal kepada publik.

---

# 9. Rancangan Database Inti

Daftar berikut merupakan baseline konseptual. Nama kolom, tipe PostgreSQL, indeks, foreign key, unique constraints, dan strategi soft delete perlu difinalkan saat audit repo dan pembuatan migration.

| Domain | Tabel inti |
|---|---|
| Identity | `users` `roles` `permissions` `model_has_roles` `model_has_permissions` |
| Customer | `customers` `customer_addresses` |
| Catalog | `products` `categories` `units` `product_prices` `product_images` |
| Supplier | `suppliers` `supplier_contacts` |
| Warehouse | `warehouses` `stock_balances` `stock_movements` `stock_reservations` |
| POS | `sales` `sale_items` `sale_payments` `cash_registers` `cash_shifts` |
| Orders | `orders` `order_items` `order_payments` `order_status_histories` |
| Procurement | `purchase_requests` `purchase_request_items` `purchase_request_approvals` |
| PO | `purchase_orders` `purchase_order_items` `purchase_order_approvals` |
| Receiving | `goods_receipts` `goods_receipt_items` `supplier_invoices` `purchase_returns` |
| Work | `order_workflows` `workflow_steps` `order_tasks` `task_assignments` `task_dependencies` |
| Task audit | `task_histories` `task_comments` `task_attachments` |
| Shipping | `shipments` `shipment_events` `order_tracking_events` |
| Finance | `financial_transactions` `payment_methods` `refunds` `accounts_payable` |
| System | `audit_logs` `notifications` `idempotency_keys` `settings` |

## Constraint yang perlu diprioritaskan

- SKU dan nomor dokumen unik sesuai scope bisnis.
- Kuantitas dan nilai uang menggunakan numeric/decimal, bukan floating point.
- Foreign key untuk relasi transaksi.
- Check constraint untuk kuantitas positif dan nilai yang valid.
- Index pada status, tanggal, customer_id, supplier_id, product_id, dan nomor dokumen.
- Gunakan UUID/ULID untuk public identifier bila sesuai, tetapi tetap pertimbangkan performa dan kebutuhan operasional.

---

# 10. UI/UX Design System

Arah visual: modern business SaaS, profesional, bersih, fokus pada keterbacaan data, dan efisien untuk pekerjaan operasional harian.

## 10.1 Color tokens

| Token | HEX | Penggunaan |
|---|---|---|
| Primary / Navy | `#142B4A` | Sidebar, heading kuat, identitas |
| Primary Blue | `#2563EB` | CTA utama, link, focus |
| Success / Emerald | `#10B981` | Selesai, sukses, stok aman |
| Teal | `#0F766E` | Aksen sekunder |
| Warning / Amber | `#D97706` | Perlu perhatian, pending |
| Danger / Red | `#DC2626` | Error, overdue, pembatalan |
| Surface | `#FFFFFF` | Card dan area konten |
| Background | `#F8FAFC` | Latar aplikasi |
| Border | `#DCE3EA` | Garis dan pemisah |
| Text | `#1F2937` | Teks utama |
| Muted | `#64748B` | Teks sekunder |

## 10.2 Typography & spacing

| Elemen | Rekomendasi |
|---|---|
| Font UI | Inter atau system sans-serif; angka tabel memakai tabular numerals |
| Page title | 24–28 px / semibold |
| Section title | 16–20 px / semibold |
| Body | 14 px / regular |
| Table | 13–14 px; tinggi baris nyaman untuk data padat |
| Caption | 12 px |
| Spacing scale | 4, 8, 12, 16, 20, 24, 32, 40 px |
| Radius | 6 px input, 8 px button/card, 12 px panel |
| Elevation | Shadow tipis; gunakan border untuk struktur utama |

## 10.3 Komponen UI wajib

Button (primary/secondary/tertiary/danger), input, textarea, select, searchable select, date picker, money input, barcode input, checkbox, radio, switch, tabs, badge/status, tooltip, modal, drawer, toast, pagination, data table, filter bar, empty state, skeleton, file upload, timeline, kanban card, stepper, approval panel, task card, product card, order summary, stock indicator.

## 10.4 Layout

Desktop: sidebar kiri, topbar dengan pencarian/notifikasi/profile, area konten dengan breadcrumb dan page actions. Mobile: navigasi ringkas, tabel berubah menjadi list/card bila perlu, aksi utama tetap mudah dijangkau. POS memakai layout khusus yang memprioritaskan pencarian produk, cart, total, dan pembayaran.

---

# 11. Struktur Menu Aplikasi

| Menu utama | Submenu |
|---|---|
| Dashboard | Overview, KPI, alert, aktivitas terbaru |
| POS | Kasir, transaksi, shift kasir, riwayat penjualan |
| Orders | Semua order, customer order, detail order, retur |
| Products | Produk, kategori, unit, harga, barcode |
| Inventory | Saldo stok, stock movement, reservasi, stock opname, adjustment |
| Procurement | Purchase request, approval PR, purchase order, approval PO |
| Receiving | Goods receipt, penerimaan parsial, purchase return |
| Work Management | Task board, my tasks, all tasks, workflow builder, SLA |
| Shipping | Pengiriman, resi, shipment events, proof of delivery |
| Customers | Daftar customer, alamat, histori order |
| Suppliers | Daftar supplier, histori pembelian, performa |
| Finance | Pembayaran, refund, kas/bank, hutang supplier, laporan |
| Reports | Penjualan, stok, pembelian, produktivitas, fulfillment |
| Settings | Users, roles, permissions, workflow, payment, shipping, system |

---

# 12. Business Rules & Kontrol Integritas

| Area | Aturan wajib |
|---|---|
| Order pricing | Harga dan diskon dihitung/validasi di server; simpan snapshot harga pada order item. |
| Stock reservation | Reservasi atomik; cegah overselling dengan transaction + row lock. |
| Order cancellation | Lepaskan reservasi sesuai tahap; jika barang sudah keluar, gunakan proses retur. |
| POS posting | Penjualan yang diposting menghasilkan payment record dan stock movement. |
| Payment webhook | Verifikasi signature, idempotency, nominal, reference, dan status sebelum update. |
| PO approval | PO belum boleh dikirim sebelum approval valid. |
| Goods receipt | Stok bertambah hanya setelah penerimaan diposting. |
| Task completion | Task hanya selesai jika kriteria dan bukti wajib terpenuhi. |
| Customer tracking | Hanya event yang ditandai public yang tampil. |
| Audit | Perubahan sensitif mencatat actor, timestamp, entity, before/after, reason. |
| Money | Gunakan decimal/numeric; simpan currency; hindari float. |
| Concurrency | Gunakan transaksi database, locking, unique constraint, dan retry yang terukur. |

## Keamanan minimum

Authentication throttling, CSRF protection, authorization per action, validasi upload, private storage untuk dokumen internal, signed/public token untuk tracking, secret management, backup PostgreSQL, log terstruktur, serta pengujian akses lintas customer wajib menjadi bagian dari release checklist.

---

# 13. Tahapan Implementasi

| Fase | Fokus | Output |
|---|---|---|
| 0. Discovery | Audit repo, dependensi, model existing, route, UI, test | Gap analysis dan migration plan |
| 1. Foundation | Laravel 13 readiness, PostgreSQL, auth, roles, design tokens | Fondasi stabil tanpa mengganggu fitur lama |
| 2. Core Inventory + POS | Catalog, stock ledger, stock balance, POS posting | Transaksi kasir dan stok konsisten |
| 3. Customer Order | Customer portal, cart, checkout, payment, reservation | Customer order end-to-end |
| 4. Work Management | Workflow templates, tasks, assignment, SLA, evidence | Tracking pekerjaan internal |
| 5. Shipping & Tracking | Shipment, resi, public tracking, delivery confirmation | Pelacakan sampai diterima |
| 6. Procurement | PR, approval, PO, goods receipt, supplier invoice | Siklus pembelian lengkap |
| 7. Finance & Reports | Cash/bank, payable, refunds, KPI, reports | Visibilitas finansial |
| 8. Hardening | Security, performance, backup, monitoring, UAT | Production readiness |

## Prinsip migrasi dari repo saat ini

Jangan mengganti framework/database secara langsung sebelum audit kompatibilitas. Buat branch migrasi, inventarisasi package Laravel, Livewire, migration, query SQL, enum, JSON, full-text search, dan fitur database-specific. Migrasi data harus diuji pada salinan database dan memiliki rencana rollback. Laravel 13 dan PostgreSQL menjadi target baseline proyek baru/hasil upgrade.

---

# 14. Acceptance Criteria Utama

| Fitur | Kriteria selesai |
|---|---|
| POS | Kasir dapat checkout; transaksi tersimpan; stok berkurang satu kali; struk dapat dicetak. |
| Customer Order | Customer dapat checkout; stok direservasi; order hanya bisa diakses pemiliknya. |
| Stock Control | Available stock benar; tidak oversell saat order paralel; semua mutasi terlacak. |
| Task Tracking | Task dibuat dari workflow; auto assign bekerja; manager dapat reassign; audit lengkap. |
| Customer Tracking | Customer melihat milestone publik yang akurat tanpa data internal. |
| PO | PR dapat diajukan; approval wajib; PO dapat diterima parsial; stok masuk saat receipt diposting. |
| Shipping | Resi dan event tersimpan; delivered dapat diverifikasi; order completion mengikuti aturan. |
| Finance | Payment/refund/payable tercatat dan dapat direkonsiliasi. |
| Security | Role/permission, IDOR, webhook replay, upload, dan audit diuji. |

## Pengujian penting

Feature tests untuk alur POS/order/PO; unit tests untuk kalkulasi harga dan status; concurrency tests untuk reservasi stok; authorization tests untuk customer dan role; webhook idempotency tests; serta end-to-end UAT untuk skenario order sampai completed.

---

# 15. Keputusan yang Masih Perlu Difinalkan

Dokumen ini mengunci keputusan yang telah disepakati, tetapi beberapa kebijakan bisnis masih perlu dipilih sebelum implementasi rinci.

| Topik | Baseline yang disarankan | Status |
|---|---|---|
| Customer registration | Public registration + admin-created customer | **Perlu konfirmasi** |
| Payment methods | Transfer manual; customer mengonfirmasi pembayaran ke WhatsApp admin, lalu admin/finance memverifikasi | **Disepakati sebagai baseline** |
| Delivery | Pickup dan delivery | **Perlu konfirmasi** |
| PO approval | Semua PO wajib approval manager pada fase awal | **Disepakati sebagai baseline** |
| Task scope | Order task terlebih dahulu; kemudian general operational task | **Perlu konfirmasi** |
| Delivery confirmation | Customer confirmation + admin evidence fallback | **Perlu konfirmasi** |
| Returns | Return merchandise workflow untuk POS dan customer order | **Perlu detail** |
| Tax & invoice | Konfigurasi pajak dan format invoice sesuai kebutuhan bisnis | **Perlu detail** |
| Courier integration | Manual entry dulu, API integration fase berikutnya | **Baseline** |

## Penutup

Blueprint ini menjadi acuan diskusi dan implementasi POS Skillage Edupark. Sebelum coding fitur baru, lakukan audit terhadap repo yang ada untuk memetakan komponen yang dapat dipertahankan, gap fungsional, risiko migrasi, dan urutan perubahan database.

---

# Lampiran: Aset SVG

Seluruh aset berupa vektor SVG yang dapat diedit. Logo dan ikon merupakan arah desain awal dan dapat disempurnakan setelah brand review. File sumber tersedia pada paket `POS_Inventory_Management_System_Design_Assets.zip` (folder `svg/`).

<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="480" viewBox="0 0 640 180" class="s3" style="max-width:100%;height:auto">
<rect width="640" height="180" rx="22" fill="#142B4A"/>
<g transform="translate(28 28)">
<rect x="0" y="0" width="124" height="124" rx="26" fill="#2563EB"/>
<path d="M28 37h68v14H42v15h47v14H42v19H28z" fill="#fff"/>
<path d="M72 78h24v21H72z" fill="#10B981"/>
<path d="M82 26h14v34H82z" fill="#10B981"/>
</g>
<text x="180" y="78" font-family="Inter,Arial,sans-serif" font-size="31" font-weight="700" fill="#fff">POS SKILLAGE</text>
<text x="180" y="116" font-family="Inter,Arial,sans-serif" font-size="25" font-weight="500" fill="#DCEAFE">EDUPARK</text>
<text x="180" y="145" font-family="Inter,Arial,sans-serif" font-size="13" letter-spacing="2" fill="#34D399">BUSINESS OPERATIONS PLATFORM</text>
</svg>
</p>

**A.1 Brand logo** `brand-logo.svg` — Horizontal brand lockup.

<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="128" viewBox="0 0 256 256" class="s4" style="max-width:100%;height:auto">
<rect width="256" height="256" rx="54" fill="#142B4A"/>
<rect x="34" y="34" width="188" height="188" rx="40" fill="#2563EB"/>
<path d="M76 77h104v22H98v24h72v22H98v34H76z" fill="#fff"/>
<path d="M147 143h33v36h-33z" fill="#10B981"/>
<path d="M158 58h22v56h-22z" fill="#10B981"/>
</svg>
</p>

**A.2 App icon** `app-icon.svg` — Ikon aplikasi persegi (256 × 256).

<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="720" viewBox="0 0 1000 650" class="s5" style="max-width:100%;height:auto">
<defs><style>.s5 .box{stroke:#cbd5e1;stroke-width:2;rx:14}.s5 .h{font:bold 21px Inter,Arial;fill:#142b4a}.s5 .p{font:15px Inter,Arial;fill:#475569}.s5 .arr{stroke:#64748b;stroke-width:3;fill:none;marker-end:url(#m5)}</style>
<marker id="m5" markerWidth="10" markerHeight="10" refX="7" refY="3" orient="auto"><path d="M0 0L8 3L0 6" fill="none" stroke="#64748b" stroke-width="1.5"/></marker></defs>
<rect width="1000" height="650" fill="#fff"/>
<text x="500" y="42" text-anchor="middle" class="h" font-size="27">SYSTEM ARCHITECTURE</text>
<rect class="box" x="110" y="75" width="780" height="75" fill="#eff6ff"/><text class="h" x="500" y="107" text-anchor="middle">Presentation Layer</text><text class="p" x="500" y="132" text-anchor="middle">Customer Portal • POS • Admin Dashboard • Employee Workspace</text>
<path class="arr" d="M500 150V185"/>
<rect class="box" x="110" y="185" width="780" height="75" fill="#ecfdf5"/><text class="h" x="500" y="217" text-anchor="middle">Application Core — Laravel 13 + Livewire</text><text class="p" x="500" y="242" text-anchor="middle">Authorization • Validation • Use Cases • Events • Notifications</text>
<path class="arr" d="M500 260V295"/>
<rect class="box" x="65" y="295" width="870" height="145" fill="#f8fafc"/>
<text class="h" x="500" y="327" text-anchor="middle">Domain Modules</text>
<g class="p" text-anchor="middle">
<text x="170" y="365">POS</text><text x="340" y="365">Orders</text><text x="510" y="365">Inventory</text><text x="680" y="365">Procurement</text><text x="835" y="365">Finance</text>
<text x="170" y="405">Work Tasks</text><text x="340" y="405">Shipping</text><text x="510" y="405">Catalog</text><text x="680" y="405">Customers</text><text x="835" y="405">Reports</text>
</g>
<path class="arr" d="M500 440V475"/>
<rect class="box" x="110" y="475" width="780" height="65" fill="#eff6ff"/><text class="h" x="500" y="514" text-anchor="middle">PostgreSQL — Transactional Data, Stock Ledger &amp; Audit Trail</text>
<path class="arr" d="M500 540V575"/>
<rect class="box" x="110" y="575" width="780" height="50" fill="#ecfdf5"/><text class="p" x="500" y="606" text-anchor="middle">External Integrations: Payment Gateway • Messaging • Courier APIs (future)</text>
</svg>
</p>

**A.3 System architecture** `system-architecture.svg` — Arsitektur sistem tingkat tinggi.

<p align="center">
<svg xmlns="http://www.w3.org/2000/svg" width="720" viewBox="0 0 900 700" class="s6" style="max-width:100%;height:auto">
<defs><style>.s6 .b{fill:#eff6ff;stroke:#cbd5e1;stroke-width:2}.s6 .g{fill:#ecfdf5;stroke:#a7f3d0;stroke-width:2}.s6 .t{font: bold 18px Inter,Arial;fill:#142b4a}.s6 .s{font:14px Inter,Arial;fill:#475569}.s6 .a{stroke:#64748b;stroke-width:3;fill:none;marker-end:url(#m6)}</style>
<marker id="m6" markerWidth="10" markerHeight="10" refX="7" refY="3" orient="auto"><path d="M0 0L8 3L0 6" fill="none" stroke="#64748b" stroke-width="1.5"/></marker></defs>
<rect width="900" height="700" fill="#fff"/>
<text x="450" y="42" text-anchor="middle" class="t" font-size="25">CUSTOMER ORDER FULFILLMENT</text>
<g>
<rect class="b" x="270" y="75" width="360" height="58" rx="12"/><text class="t" x="450" y="110" text-anchor="middle">Order Confirmed</text>
<path class="a" d="M450 133V165"/>
<rect class="b" x="270" y="165" width="360" height="58" rx="12"/><text class="t" x="450" y="200" text-anchor="middle">Generate &amp; Assign Tasks</text>
<path class="a" d="M450 223V255"/>
<rect class="g" x="270" y="255" width="360" height="58" rx="12"/><text class="t" x="450" y="290" text-anchor="middle">Picking → Quality Control</text>
<path class="a" d="M450 313V345"/>
<rect class="g" x="270" y="345" width="360" height="58" rx="12"/><text class="t" x="450" y="380" text-anchor="middle">Packing &amp; Ready to Ship</text>
<path class="a" d="M450 403V435"/>
<rect class="b" x="270" y="435" width="360" height="58" rx="12"/><text class="t" x="450" y="470" text-anchor="middle">Shipment &amp; Tracking Events</text>
<path class="a" d="M450 493V525"/>
<rect class="g" x="270" y="525" width="360" height="58" rx="12"/><text class="t" x="450" y="560" text-anchor="middle">Delivery Confirmed → Completed</text>
<text class="s" x="450" y="635" text-anchor="middle">Internal tasks remain private; selected milestones are published to customer tracking.</text>
</g></svg>
</p>

**A.4 Order fulfillment workflow** `order-fulfillment-workflow.svg` — Alur pemenuhan customer order. Task internal tetap privat; milestone terpilih dipublikasikan ke customer tracking.

## Palet warna aset

| Nama | HEX |
|---|---|
| Navy | `#142B4A` |
| Blue | `#2563EB` |
| Emerald | `#10B981` |
| Teal | `#0F766E` |
| Text | `#1F2937` |
| Muted | `#64748B` |

---
