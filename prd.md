# Kref Barber — Product Requirements Document

> **Product:** Kref Barber Online Booking & Appointment Management
> **Client:** Kref Barber — `@krefbraber`
> **Version:** 1.3
> **Date:** 17 August 2026
> **Status:** Draft
> **Changelog v1.3:**
> - **Payment type fleksibel (Online):** Customer dapat memilih **DP** atau **Full Payment** saat checkout booking online.
> - **Pelunasan:** Jika customer memilih DP, sisa pembayaran (pelunasan) dilakukan **setelah layanan cukur selesai**, dicatat oleh Admin.
> - **Product upselling:** Barber dapat menawarkan produk (mis. pomade, shampoo) selama layanan berlangsung. Produk **ditambahkan oleh Admin** ke transaksi booking yang sedang berjalan — barber tetap tidak memiliki akses sistem.
> - **Multiple payment per booking:** Satu booking kini dapat memiliki **lebih dari satu record payment**, dengan metode berbeda-beda (mis. service dibayar Full Payment via Midtrans, product dibayar terpisah via QRIS statis).
> - **Walk-in tanpa pembayaran di awal:** Walk-in tidak lagi meminta metode pembayaran saat booking dibuat. Seluruh transaksi (service + product) dibayar **setelah layanan selesai**.
> - **Booking items dipisah per tipe:** `booking_items` kini membedakan `service` dan `product` secara eksplisit; `payments` menjadi relasi **one-to-many** terhadap `bookings`.
>
> **Changelog v1.2:** Menyederhanakan role sistem menjadi hanya **Admin** dan **User (Customer)**. Barber tidak lagi memiliki akun login — barber adalah entitas data yang dikelola sepenuhnya oleh Admin (profil, jadwal, availability). Seluruh aksi operasional (termasuk input walk-in) dilakukan oleh Admin.
> **Changelog v1.1:** Menambahkan Bagian 9A — Walk-in Booking Handling.

## 1. Product Overview

Kref Barber membutuhkan platform booking resmi untuk menggantikan proses booking manual melalui WhatsApp/Instagram DM.

Website memiliki tiga tujuan utama:

1. **Booking** — customer dapat melakukan booking secara mandiri, dengan pilihan pembayaran DP atau Full Payment.
2. **Schedule Management** — customer dapat melihat availability barber secara real-time.
3. **Branding** — website menjadi digital storefront resmi Kref Barber.

Website harus terasa sebagai **brand experience Kref Barber**, bukan sekadar aplikasi booking generik.

Platform juga harus mengakomodasi realitas operasional barbershop: **tidak semua customer datang melalui booking online.** Sebagian besar barbershop tetap menerima **walk-in customer** (datang langsung tanpa booking). Sistem harus mencatat walk-in ke dalam data booking yang sama agar availability, revenue, dan histori tetap akurat — lihat Bagian 9A.

Platform juga harus mengakomodasi **product upselling** — barber menawarkan produk tambahan (di luar service) kepada customer di tengah layanan, dan **transaksi dapat dibayar dengan lebih dari satu metode pembayaran** dalam satu booking — lihat Bagian 9B & 13.

**Catatan penting soal role (v1.2, tetap berlaku):** Sistem hanya memiliki **dua role login**: `admin` dan `user` (customer). **Barber bukan role login** — barber adalah data operasional (nama, foto, spesialisasi, jadwal) yang seluruhnya dikelola oleh Admin. Barber tidak memiliki akses ke sistem sama sekali — termasuk untuk menawarkan produk, yang secara sistem tetap diinput oleh Admin.

### Tech Stack

* **Backend:** Laravel
* **Frontend:** Laravel Blade
* **UI:** Bootstrap 5
* **Database:** MySQL
* **Payment:** Midtrans + pencatatan manual (cash, QRIS statis)
* **Authentication:** Laravel Authentication

---

# 2. Problem Statement

### Current Problems

* Booking masih bergantung pada WhatsApp/Instagram DM.
* Customer harus menunggu respons admin.
* Tidak ada sistem booking terpusat.
* Informasi service, harga, barber, dan jadwal belum terstruktur.
* Payment/DP berpotensi membutuhkan tracking manual.
* Instagram belum terhubung langsung dengan proses booking.
* Walk-in customer tidak tercatat di sistem yang sama dengan booking online, sehingga berisiko bentrok dengan slot yang sudah dibooking online (lihat Bagian 9A).
* Penjualan produk tambahan (upselling) oleh barber saat layanan belum tercatat secara terstruktur, sehingga revenue dari produk berpotensi tidak masuk laporan.
* Pembayaran yang terpecah (sebagian via transfer/Midtrans, sebagian cash/QRIS di tempat) belum bisa direpresentasikan dalam satu booking.

### Business Problems to Validate

* Seberapa sering terjadi double booking?
* Berapa volume booking harian/mingguan?
* Seberapa besar customer batal karena lambatnya respons admin?
* Apakah customer membutuhkan real-time availability?
* Apakah histori booking diperlukan untuk kebutuhan bisnis?
* Berapa proporsi walk-in vs booking online saat ini?
* Apakah admin tunggal (single admin) cukup untuk menangani seluruh operasional input walk-in + booking online + pencatatan pelunasan + input produk, atau dibutuhkan lebih dari satu akun admin (mis. per shift)?
* **Seberapa sering barber menawarkan produk tambahan saat layanan, dan seberapa besar kontribusinya terhadap revenue?** — relevan untuk memprioritaskan UX product upselling.
* **Berapa proporsi booking DP yang gagal melunasi (walk-out tanpa bayar sisa)?** — relevan untuk kebijakan bad debt.

---

# 3. Product Goals

| Goal                      | Success Indicator                                                   |
| ------------------------- | --------------------------------------------------------------------- |
| Self-service booking      | ≥70% booking baru melalui website                                    |
| Real-time availability    | Tidak ada false-available slot                                       |
| Double booking prevention | 0 insiden double booking (termasuk bentrok online vs walk-in)        |
| Midtrans payment          | ≥95% payment berhasil diproses                                       |
| Payment verification      | Status booking/payment selalu konsisten                              |
| Flexible payment          | Customer dapat memilih DP atau Full Payment tanpa error kalkulasi     |
| Pelunasan tercatat         | 100% booking DP yang selesai layanan memiliki status pelunasan yang jelas (lunas/outstanding) |
| Product upselling tercatat | 100% produk yang ditambahkan barber tercatat di booking oleh Admin, dengan harga dari master data |
| Multiple payment per booking | Sistem dapat mencatat >1 payment record per booking tanpa inkonsistensi total tagihan |
| Admin management          | Booking, barber, service, product, schedule dapat dikelola melalui dashboard |
| Booking confirmation      | 100% paid booking mendapatkan confirmation                           |
| Walk-in recording         | 100% walk-in customer tercatat di sistem yang sama dengan booking online, diinput oleh Admin, tanpa perlu pembayaran di awal |

---

# 4. Non-Goals — MVP

Fitur berikut tidak termasuk MVP:

* Membership
* Loyalty point
* Referral
* Product marketplace (customer membeli produk secara mandiri lewat website — produk hanya dapat ditambahkan ke booking oleh Admin, bukan self-checkout)
* Multi-outlet
* Advanced CRM
* AI hairstyle recommendation
* Subscription
* Native mobile application
* Akun login/akses sistem untuk barber (barber tetap sebagai data, bukan user sistem — termasuk untuk input produk yang ditawarkannya)
* Kios/self-check-in digital untuk walk-in (customer input sendiri di tempat) — untuk MVP, input walk-in dilakukan oleh **admin**, bukan oleh barber atau customer sendiri.
* Manajemen stok produk (inventory tracking) — MVP hanya mencatat harga & penjualan produk pada booking, bukan pengurangan stok otomatis (lihat Open Questions).
* Integrasi otomatis QRIS statis dengan sistem (rekonsiliasi otomatis) — pencatatan QRIS statis dilakukan manual oleh Admin.

Fitur tersebut dapat dipertimbangkan pada fase berikutnya.

---

# 5. User Roles

Sistem hanya memiliki **dua role login**:

```text
user   (customer)
admin
```

## User (Customer)

Dapat:

* Melihat website
* Melihat service
* Melihat barber
* Melihat availability
* Membuat booking
* **Memilih metode pembayaran booking: DP atau Full Payment**
* Membayar (DP atau Full) via Midtrans
* Melihat booking
* Melihat booking history
* Melihat status pelunasan (lunas / outstanding) pada booking miliknya

Tidak dapat:

* Mengubah harga
* Mengubah schedule
* Menambahkan produk ke booking sendiri (hanya Admin yang dapat menambahkan produk)
* Mengakses data user lain
* Mengakses admin panel

## Admin

Dapat:

* Mengelola customer (data user)
* **Mengelola barber sepenuhnya** — barber adalah data yang dikelola admin, bukan akun terpisah (create, edit, foto, spesialisasi, jadwal, aktif/nonaktif)
* Mengelola service
* **Mengelola product (master data produk upselling)**
* Mengelola schedule (untuk setiap barber)
* Mengelola booking (online & walk-in)
* **Menginput booking walk-in untuk barber mana pun** (satu-satunya jalur input walk-in — lihat Bagian 9A)
* **Menambahkan product ke booking yang sedang berjalan** (upselling — lihat Bagian 9B)
* **Mencatat pelunasan (sisa pembayaran) untuk booking DP setelah layanan selesai**
* **Mencatat multiple payment record dengan metode berbeda pada satu booking**
* Monitoring payment (termasuk status outstanding/pelunasan)
* Melihat dashboard dan revenue (termasuk breakdown service vs product)
* Melakukan manual override jika diperlukan

**Catatan desain:** Karena barber bukan role login, **tabel `barbers` tidak memerlukan relasi ke tabel `users`/akun login sama sekali** — barber murni entitas data operasional. Ini menyederhanakan RBAC (hanya 2 middleware role) dan menghilangkan kebutuhan akan flow "barber login/lupa password/dsb." dari scope MVP. Hal ini juga berlaku untuk product upselling: barber menawarkan secara verbal/fisik, Admin yang menginput ke sistem.

---

# 6. Target Users

### Existing Customer (User)

Membutuhkan booking cepat tanpa menunggu respons admin, dapat memilih barber favorit, dan fleksibel memilih membayar DP atau lunas di awal.

### New Customer (User)

Datang dari Instagram/Google dan membutuhkan:

* Informasi service
* Harga
* Barber
* Portfolio
* Lokasi
* Jam operasional
* Booking langsung
* Opsi pembayaran yang jelas (DP vs Full)

### Walk-in Customer

Datang langsung ke lokasi tanpa booking sebelumnya. Tidak berinteraksi dengan website maupun barber secara sistem — seluruh input ke sistem dilakukan oleh **Admin** di tempat. Walk-in customer **tidak membayar di awal** — pembayaran (service + product apa pun yang diambil) dilakukan setelah layanan selesai.

### Admin

Membutuhkan sistem terpusat untuk:

* Booking (online & walk-in)
* Data & schedule barber
* Data produk (master data upselling)
* Payment (termasuk multi-payment & pelunasan)
* Service

**Barber** sendiri, dalam konteks produk ini, adalah **objek data** (seperti halnya "service" atau "product") — bukan aktor yang berinteraksi langsung dengan sistem, meskipun secara operasional barber-lah yang menawarkan produk kepada customer.

---

# 7. Main User Flow

```text
Instagram / Google
        ↓
   Landing Page
        ↓
 Services / Barbers
        ↓
      Book Now
        ↓
   Choose Service
        ↓
    Choose Barber
        ↓
     Choose Date
        ↓
     Choose Time
        ↓
 Customer Information
        ↓
   Booking Summary
        ↓
 Choose Payment Type (DP / Full Payment)
        ↓
      Payment
        ↓
     Midtrans
        ↓
 Payment Verification
        ↓
 Booking Confirmation
        ↓
   (Hari-H) Layanan Berlangsung
        ↓
  (Opsional) Admin Tambah Product
        ↓
 (Jika DP) Pelunasan oleh Admin
        ↓
       COMPLETED
```

Booking harus dapat diselesaikan dengan langkah seminimal mungkin.

*(Untuk alur walk-in, lihat Bagian 9A — seluruhnya dijalankan oleh Admin, bukan barber. Untuk alur product upselling, lihat Bagian 9B.)*

---

# 8. Public Website

## Landing Page

Minimal terdiri dari:

* Navbar
* Hero
* Services
* Barbers
* Gallery/Portfolio
* About Kref Barber
* Testimonials
* Location
* Opening Hours
* Instagram CTA
* Footer

### Primary CTA

> **Book Your Cut**

Website harus mengutamakan **mobile-first experience** karena sebagian traffic diperkirakan berasal dari Instagram.

---

# 9. Booking System (Online)

## Booking Flow

Customer:

1. Memilih service.
2. Memilih barber.
3. Memilih tanggal.
4. Memilih time slot.
5. Mengisi informasi.
6. Melihat booking summary.
7. **Memilih metode pembayaran: DP atau Full Payment.**
8. Membayar sesuai pilihan (DP atau Full) via Midtrans.

### Customer Information

Minimal:

* Nama
* Nomor WhatsApp
* Email — optional
* Catatan — optional

### Booking Summary

Tampilkan:

* Service
* Barber
* Date
* Time
* Duration
* Total price (service — belum termasuk produk yang mungkin ditambahkan nanti)
* **Payment type yang dipilih (DP / Full Payment)**
* Jika DP: nominal DP yang harus dibayar sekarang, dan estimasi sisa yang akan dilunasi setelah layanan selesai
* Jika Full Payment: total yang dibayar sekarang
* Booking status

### Payment Type Selection (Baru — v1.3)

* Customer memilih salah satu di step checkout:
  * **DP** — membayar sebagian nominal (mengikuti aturan DP di Bagian 14) sekarang via Midtrans; sisa dibayar setelah layanan cukur selesai (**pelunasan** — lihat Bagian 9C).
  * **Full Payment** — membayar seluruh total service sekarang via Midtrans.
* Pilihan ini hanya menentukan **nominal yang dibayar di awal untuk service**. Jika customer mengambil produk tambahan (product upselling) selama layanan, tagihan produk tersebut **selalu** ditagihkan/dibayar terpisah setelah ditambahkan oleh Admin (lihat Bagian 9B & 13) — tidak termasuk dalam kalkulasi DP/Full Payment service di awal.
* Booking dengan Full Payment tetap dapat memiliki tagihan tambahan jika customer mengambil produk saat layanan — dalam kasus ini booking tetap `PAID`/`CONFIRMED` untuk komponen service, namun akan ada payment record tambahan khusus produk yang perlu dilunasi terpisah (lihat Bagian 9B.5).

---

# 9A. Walk-in Booking Handling

### 9A.1 Objective

Memungkinkan **admin** mencatat customer yang datang langsung ke lokasi tanpa booking online, ke dalam sistem yang **sama** dengan booking online — agar availability, revenue, dan histori tetap akurat dan tidak ada slot yang bisa dibooking dua kali (online vs walk-in).

### 9A.2 Actor

**Admin — satu-satunya aktor yang dapat menginput walk-in.** (Barber tidak memiliki akses sistem, sehingga tidak dapat menginput walk-in sendiri; seluruh proses dilakukan oleh admin yang bertugas di lokasi.)

### 9A.3 Precondition

Barber dan service terkait sudah terdaftar di sistem (oleh admin) dan berstatus aktif.

### 9A.4 User Flow — Admin Input Walk-in (v1.3: tanpa pembayaran di awal)

1. Admin membuka menu **"Booking Baru (Walk-in)"** di admin panel.
2. Admin memilih **barber** yang akan melayani.
3. Sistem menampilkan slot yang tersedia **saat itu juga** (real-time), menggunakan availability engine yang sama dengan booking online (Bagian 10).
4. Admin memilih **service** yang diambil customer (bisa lebih dari satu service dalam satu kunjungan).
5. Admin mengisi data customer minimal: **nama** dan **nomor HP/WhatsApp** (opsional, tergantung kebutuhan bisnis — lihat Open Questions).
6. Sistem membuat booking dengan `source = walk_in`, status `CONFIRMED`, **`payment_status = unpaid`**, dan slot langsung terkunci (tidak lagi muncul sebagai available untuk booking online). **Tidak ada pemilihan metode pembayaran di langkah ini** — walk-in tidak membayar apa pun saat booking dibuat.
7. Layanan berlangsung. Jika barber menawarkan produk tambahan selama layanan, Admin menambahkannya ke booking yang sama (lihat Bagian 9B).
8. Setelah layanan selesai, Admin membuka booking tersebut dan mencatat **pembayaran penuh** (service + product apa pun yang ditambahkan) menggunakan satu atau lebih metode pembayaran (cash, QRIS statis, dll. — lihat Bagian 9C & 13), lalu menandai booking sebagai `COMPLETED`.

### 9A.5 System Behavior

- Walk-in booking **menggunakan availability engine yang sama** dengan booking online (Bagian 10) — bukan sistem terpisah. Ini memastikan tidak ada kemungkinan slot yang sama diambil dua kali oleh online dan walk-in secara bersamaan.
- Karena walk-in dibuat langsung oleh admin di lokasi (bukan melalui checkout customer), proses **temporary hold** yang ada pada booking online (Bagian 9, checkout flow) tidak diperlukan — begitu admin submit, slot langsung dikunci dalam transaksi database yang sama dengan mekanisme locking pada Bagian 11 (Double Booking Prevention).
- Walk-in booking **tidak melalui Midtrans di awal** dan **tidak meminta metode pembayaran saat dibuat**. Seluruh pembayaran (service + product) dicatat secara manual oleh admin **setelah layanan selesai** — lihat Bagian 9C.
- Data service, harga, dan durasi tetap diambil dari master data backend (bukan input bebas admin) — mempertahankan Critical Business Rule #1 & #2 (Bagian 30), kecuali admin diberi izin eksplisit untuk memberi diskon/penyesuaian manual (**Business Decision Required**).

### 9A.6 Business Rules

- Setiap booking (baik online maupun walk-in) memiliki kolom `source` (`online` / `walk_in`) untuk membedakan asal booking pada laporan dan analytics.
- Walk-in booking tunduk pada aturan Double Booking Prevention yang sama (Bagian 11) — tidak ada slot khusus yang "dikecualikan" dari pengecekan.
- Slot yang sudah terisi walk-in **langsung hilang dari availability online** secara real-time (karena keduanya membaca dari tabel `bookings` yang sama).
- **Walk-in tidak wajib (dan tidak diminta) membayar apa pun saat booking dibuat** — seluruh pembayaran ditangani penuh setelah layanan selesai (lihat Bagian 9C).
- **Hanya Admin yang dapat mengakses fitur ini** — tidak ada jalur input walk-in di luar admin panel.
- Jika Kref Barber ingin menyediakan **buffer slot** khusus untuk walk-in (mis. tidak mengizinkan booking online penuh 100% agar tetap ada ruang untuk walk-in), ini adalah keputusan bisnis terpisah — lihat Open Questions (Bagian 29).

### 9A.7 Open Questions Terkait Walk-in *(ditambahkan ke Bagian 29)*

- Apakah nomor HP/WhatsApp **wajib** diisi untuk walk-in, atau boleh dikosongkan demi mempercepat proses input di kasir?
- Apakah perlu **buffer/alokasi slot** yang sengaja tidak dibuka untuk booking online agar tetap tersedia untuk walk-in di jam-jam ramai?
- **Jika hanya ada satu admin bertugas di lokasi pada satu waktu, apakah kecepatan proses input walk-in (Bagian 9A.4, target < 1 menit) cukup untuk menangani jam-jam ramai tanpa mengganggu operasional kasir?**
- **Bagaimana kebijakan jika walk-in customer pergi tanpa membayar setelah layanan selesai (bad debt)?** — lihat Bagian 28 (Risks).

### 9A.8 Error Cases

| Case | Expected Behavior |
|---|---|
| Admin mencoba input walk-in pada slot yang ternyata baru saja diambil booking online | Sistem menolak, menampilkan slot terbaru yang tersedia (mekanisme locking sama seperti Bagian 11) |
| Barber yang dipilih sedang tidak aktif/dinonaktifkan | Sistem mencegah pembuatan booking pada barber tersebut |
| Admin lupa memilih service | Validasi wajib — booking walk-in tidak dapat disimpan tanpa minimal satu service |
| Koneksi terputus saat admin submit walk-in | Sama seperti booking online — transaction rollback, tidak ada booking "setengah jadi" |
| Tidak ada admin yang login/bertugas saat walk-in datang | Di luar scope sistem — merupakan SOP operasional Kref Barber (mis. selalu ada minimal satu perangkat admin aktif di kasir selama jam operasional) |
| Walk-in customer pergi sebelum Admin sempat mencatat pembayaran akhir | Booking tetap `CONFIRMED`/`unpaid`, muncul di dashboard sebagai outstanding — SOP internal menangani penagihan/write-off |

### 9A.9 Acceptance Criteria

- **Given** Barber A memiliki slot 14:00 yang kosong, **When** admin menginput walk-in customer pada slot tersebut, **Then** sistem membuat booking dengan `source = walk_in`, `payment_status = unpaid`, status `CONFIRMED`, dan slot 14:00 langsung tidak lagi muncul sebagai available di website booking online — **tanpa meminta metode pembayaran apa pun pada langkah ini**.
- **Given** slot 14:00 Barber A sudah diisi walk-in, **When** customer lain mencoba booking online pada slot & barber yang sama, **Then** sistem menolak dan slot tersebut tidak ditampilkan sebagai available.
- **Given** admin membuka menu walk-in, **When** admin memilih barber dan tanggal hari ini, **Then** sistem hanya menampilkan slot yang benar-benar kosong berdasarkan availability engine yang sama dengan booking online.
- **Given** user dengan role `user` (customer) mencoba mengakses route walk-in admin, **When** request dikirim, **Then** sistem menolak akses (403) karena hanya role `admin` yang diizinkan.
- **Given** booking walk-in telah selesai layanan, **When** admin mencatat pembayaran penuh (service + product) dengan satu atau lebih metode, **Then** `payment_status` booking berubah menjadi `paid_full` dan booking dapat ditandai `COMPLETED`.

---

# 9B. Product Upselling

### 9B.1 Objective

Memungkinkan barber menawarkan produk tambahan (mis. pomade, shampoo, hair tonic) kepada customer selama layanan berlangsung, dan mencatat penjualan tersebut ke dalam booking yang sama — baik untuk booking online maupun walk-in — sehingga revenue produk tergabung dalam satu laporan yang akurat.

### 9B.2 Actor

- **Barber** — menawarkan produk secara **verbal/fisik** kepada customer. Barber **tidak berinteraksi dengan sistem sama sekali** (konsisten dengan Bagian 5 & 9A.2).
- **Admin** — satu-satunya pihak yang **menginput** produk ke dalam booking di sistem, setelah customer menyetujui pembelian.

### 9B.3 Precondition

- Booking (online atau walk-in) sudah berstatus `CONFIRMED` dan sedang/akan berlangsung.
- Produk yang ditawarkan sudah terdaftar sebagai master data produk (aktif) oleh Admin.

### 9B.4 User Flow — Admin Menambahkan Produk

1. Selama layanan berlangsung, barber menawarkan produk kepada customer secara langsung (di luar sistem).
2. Jika customer setuju, barber menginformasikan produk yang diambil kepada Admin (lisan/catatan di kasir).
3. Admin membuka booking terkait di admin panel (baik booking online maupun walk-in) dan memilih **"Tambah Produk"**.
4. Admin memilih produk dari master data produk (nama, harga otomatis terisi dari backend) dan jumlah (qty).
5. Sistem menambahkan produk sebagai `booking_item` baru dengan `item_type = product`, terhubung ke booking yang sama, menggunakan snapshot harga saat ditambahkan.
6. Total tagihan booking (`total_amount`) otomatis bertambah sejumlah harga produk × qty.
7. Produk yang ditambahkan **belum otomatis dianggap dibayar** — statusnya menambah `outstanding_amount` booking hingga ada payment record yang menutupinya (lihat Bagian 9B.5 & 13).

### 9B.5 System Behavior

- Produk dapat ditambahkan pada booking **online maupun walk-in**, kapan pun selama booking belum `COMPLETED`.
- Produk **hanya dapat ditambahkan oleh Admin** — tidak ada jalur bagi customer atau barber untuk menambahkan produk sendiri ke sistem.
- Harga produk **selalu berasal dari master data backend** pada saat ditambahkan (snapshot), konsisten dengan Critical Business Rule harga service (Bagian 30) — kecuali Admin diberi izin eksplisit untuk diskon manual (Business Decision Required, sama seperti Bagian 9A.7).
- Penambahan produk **tidak mengubah slot/availability** — hanya memengaruhi `total_amount` dan `outstanding_amount` booking, bukan jadwal.
- Untuk booking online yang sudah **Full Payment** di awal (hanya mencakup service), produk yang ditambahkan setelahnya membuat booking memiliki **outstanding amount baru** yang harus dibayar terpisah (biasanya di lokasi, setelah layanan) — lihat Bagian 13.
- Untuk booking online dengan **DP**, produk yang ditambahkan digabung dengan sisa pembayaran service saat pelunasan (Bagian 9C), sehingga customer melunasi sisa service + produk sekaligus.
- Untuk **walk-in**, produk yang ditambahkan digabung ke dalam total pembayaran yang dilakukan setelah layanan selesai (Bagian 9C).

### 9B.6 Business Rules

- Setiap `booking_item` wajib memiliki `item_type` (`service` atau `product`) untuk memisahkan pelaporan revenue service vs produk.
- Produk mengikuti aturan snapshot yang sama dengan service (Bagian 20) — perubahan harga master produk di kemudian hari tidak mengubah harga pada booking yang sudah tercatat.
- Produk yang dinonaktifkan (`is_active = false`) tidak dapat ditambahkan ke booking baru, namun tidak menghapus histori produk yang sudah tercatat pada booking sebelumnya (konsisten dengan Critical Business Rule #8).
- Manajemen stok produk **tidak termasuk MVP** — sistem mencatat penjualan produk pada booking, tapi tidak mengurangi stok secara otomatis (lihat Open Questions).

### 9B.7 Error Cases

| Case | Expected Behavior |
|---|---|
| Admin mencoba menambahkan produk yang tidak aktif | Sistem menolak, produk tidak muncul di daftar pilihan |
| Admin mencoba menambahkan produk ke booking yang sudah `COMPLETED` atau `CANCELLED` | Sistem menolak — produk hanya dapat ditambahkan selama booking aktif (belum selesai/dibatalkan) |
| Koneksi terputus saat admin menambahkan produk | Transaction rollback, tidak ada `booking_item` "setengah jadi" |

### 9B.8 Acceptance Criteria

- **Given** booking online (Full Payment, service saja) berstatus `CONFIRMED`, **When** Admin menambahkan 1 produk seharga Rp50.000, **Then** `total_amount` booking bertambah Rp50.000 dan `outstanding_amount` booking menjadi Rp50.000 meski status service sudah `PAID`.
- **Given** booking walk-in belum ada pembayaran sama sekali, **When** Admin menambahkan produk sebelum layanan selesai, **Then** produk ikut terhitung dalam total tagihan yang dibayar customer di akhir (Bagian 9C).
- **Given** produk X memiliki harga Rp30.000 saat ditambahkan ke booking, **When** Admin mengubah harga master produk X menjadi Rp40.000 setelahnya, **Then** `booking_item` yang sudah tercatat tetap menampilkan Rp30.000 (snapshot).

---

# 9C. Pelunasan & Multiple Payment

### 9C.1 Objective

Mengakomodasi kenyataan bahwa satu booking dapat dilunasi secara bertahap dan/atau dengan lebih dari satu metode pembayaran — baik karena skema DP, penambahan produk di tengah layanan, maupun kebiasaan walk-in yang membayar sepenuhnya di akhir.

### 9C.2 Konsep Dasar

- Setiap booking memiliki **`total_amount`** (dihitung dari seluruh `booking_items`: service + product yang aktif pada booking tersebut) dan **`outstanding_amount`** (`total_amount` dikurangi jumlah seluruh `payments` berstatus sukses pada booking tersebut).
- `payments` adalah relasi **one-to-many** terhadap `bookings` — satu booking dapat memiliki banyak record payment, masing-masing dengan `method`, `amount`, `status`, dan `recorded_by`/`midtrans_transaction_id` sendiri.
- `payment_status` booking dihitung/di-derive dari perbandingan `outstanding_amount` terhadap `total_amount`:
  - `unpaid` — belum ada pembayaran sukses sama sekali.
  - `partial` — sudah ada pembayaran, tapi `outstanding_amount` > 0 (mis. baru bayar DP, atau baru bayar service tapi belum bayar produk tambahan).
  - `paid_full` — `outstanding_amount` = 0.

### 9C.3 Skenario Pembayaran

| Skenario | Payment record yang tercatat |
|---|---|
| Online, Full Payment, tanpa produk | 1 payment: `midtrans`, sejumlah total service |
| Online, DP, tanpa produk | 2 payment: (1) `midtrans` — DP saat booking; (2) pelunasan setelah layanan — metode bebas (cash/qris_static/midtrans), dicatat Admin |
| Online, Full Payment, + produk ditambahkan saat layanan | 2 payment: (1) `midtrans` — full service saat booking; (2) pembayaran produk setelah layanan — metode bebas, dicatat Admin |
| Online, DP, + produk ditambahkan saat layanan | 2 payment: (1) `midtrans` — DP saat booking; (2) pelunasan (sisa service + produk digabung) setelah layanan, dicatat Admin |
| Walk-in, tanpa produk | 1 payment: dicatat Admin setelah layanan selesai (bisa lebih dari 1 metode, mis. split cash + QRIS) |
| Walk-in, + produk | 1 atau lebih payment: dicatat Admin setelah layanan selesai, mencakup service + produk, metode bisa berbeda-beda per payment |
| Contoh split metode | Service Rp100.000 dibayar via Midtrans (Full Payment di awal); produk Rp50.000 dibayar via QRIS statis setelah layanan → 2 payment record berbeda metode pada 1 booking |

### 9C.4 User Flow — Admin Mencatat Pelunasan / Pembayaran Tambahan

1. Admin membuka booking yang memiliki `outstanding_amount` > 0 (ditandai di dashboard/detail booking).
2. Admin memilih **"Catat Pembayaran"**.
3. Sistem menampilkan `outstanding_amount` saat ini.
4. Admin memasukkan nominal yang dibayar (dapat kurang dari outstanding jika dibayar bertahap/split metode) dan memilih metode: `cash`, `qris_static`, atau `midtrans` (jika menggunakan link pembayaran Midtrans manual — opsional, TBD).
5. Sistem membuat record baru di `payments` terkait booking tersebut, mengurangi `outstanding_amount`.
6. Jika `outstanding_amount` mencapai 0, `payment_status` booking berubah menjadi `paid_full`.
7. Admin dapat menandai booking `COMPLETED` (lihat Bagian 12 — pelunasan penuh direkomendasikan sebelum `COMPLETED`, lihat Business Rule di Bagian 9C.5).

### 9C.5 Business Rules

- Backend **wajib** memvalidasi bahwa jumlah seluruh `payments` sukses pada satu booking **tidak pernah melebihi** `total_amount` booking tersebut pada saat itu (mencegah overpayment tercatat keliru).
- `total_amount` bersifat **dinamis** selama booking belum `COMPLETED` (dapat bertambah jika ada produk baru ditambahkan) — namun payment yang **sudah tercatat sukses tidak berubah/dihapus** ketika `total_amount` bertambah; hanya `outstanding_amount` yang di-recalculate.
- Setiap payment record wajib menyimpan `method`, `amount`, `status`, `recorded_by` (untuk yang dicatat manual oleh admin) atau `midtrans_transaction_id` (untuk yang melalui Midtrans).
- Direkomendasikan (Business Decision Required): booking **tidak dapat ditandai `COMPLETED`** apabila `payment_status` masih `unpaid`/`partial`, kecuali Admin melakukan override manual (harus tercatat di audit log) — lihat Open Questions.
- Midtrans webhook tetap hanya memengaruhi payment record yang bersangkutan (idempotent per record), bukan seluruh booking — penting karena satu booking kini bisa punya beberapa payment dengan sumber berbeda (Midtrans vs manual).

### 9C.6 Error Cases

| Case | Expected Behavior |
|---|---|
| Admin mencoba mencatat pembayaran melebihi `outstanding_amount` | Sistem menolak/memberi peringatan — nominal payment tidak boleh membuat total pembayaran melebihi `total_amount` |
| Dua admin mencoba mencatat pelunasan booking yang sama secara bersamaan (mis. shift berbeda) | Database transaction + locking memastikan `outstanding_amount` ter-update secara atomik, tidak terjadi double-record |
| Produk ditambahkan setelah booking sudah `paid_full` (mis. Full Payment tanpa produk awal) | `payment_status` otomatis kembali menjadi `partial` karena `outstanding_amount` > 0 lagi |
| Midtrans webhook untuk payment DP datang terlambat, sementara Admin sudah mencatat pelunasan manual secara penuh (termasuk asumsi DP belum masuk) | Berpotensi overpayment tercatat — sistem harus menampilkan peringatan/reconciliation di dashboard; SOP admin menunggu status Midtrans sebelum mencatat pelunasan manual bila memungkinkan |

### 9C.7 Acceptance Criteria

- **Given** booking online dengan DP Rp50.000 dari total service Rp150.000, **When** Midtrans mengonfirmasi pembayaran DP, **Then** `payment_status` booking menjadi `partial` dan `outstanding_amount` = Rp100.000.
- **Given** booking di atas telah selesai layanan tanpa produk tambahan, **When** Admin mencatat pelunasan Rp100.000 via cash, **Then** `payment_status` menjadi `paid_full` dan `outstanding_amount` = 0.
- **Given** booking Full Payment service Rp150.000 sudah lunas, **When** Admin menambahkan produk Rp50.000, **Then** `total_amount` menjadi Rp200.000, `outstanding_amount` menjadi Rp50.000, dan `payment_status` berubah dari `paid_full` menjadi `partial`.
- **Given** booking walk-in dengan service Rp100.000 + produk Rp50.000 (`total_amount` = Rp150.000), **When** customer membayar Rp100.000 via Midtrans link dan Rp50.000 via QRIS statis (dicatat admin), **Then** sistem mencatat 2 payment record berbeda metode dan `payment_status` menjadi `paid_full` setelah keduanya sukses.

---

# 10. Availability System

Availability dihitung berdasarkan:

```text
Working Schedule
       -
Break Time
       -
Day Off
       -
Blocked Slot
       -
Existing Booking (online + walk-in)
       =
Available Slot
```

Booking aktif yang harus memblokir slot (berlaku untuk **kedua sumber**, online maupun walk-in):

```text
PENDING
WAITING_PAYMENT
PAID
CONFIRMED
```

Booking berikut membuat slot tersedia kembali:

```text
EXPIRED
CANCELLED
```

Availability harus dihitung oleh **backend**, bukan hanya JavaScript/frontend, dan **wajib memperhitungkan booking dari kedua sumber (online & walk-in)** karena keduanya disimpan di tabel `bookings` yang sama (lihat Bagian 20 & 9A.5).

**Catatan v1.3:** Availability slot **tidak dipengaruhi** oleh `payment_status` (unpaid/partial/paid_full) maupun penambahan produk — slot tetap terkunci selama status booking termasuk dalam daftar di atas, terlepas dari apakah service sudah dibayar penuh, DP, atau belum sama sekali (walk-in).

Working schedule barber (jam kerja, break, day off, blocked slot) **seluruhnya diatur oleh Admin** melalui panel Schedule Management — barber tidak dapat mengatur jadwalnya sendiri karena tidak memiliki akses login.

---

# 11. Double Booking Prevention

Sistem **wajib** mencegah dua booking aktif — baik online maupun walk-in (yang keduanya hanya dapat dibuat lewat jalur customer atau admin) — terjadi pada:

```text
Barber yang sama
+
Tanggal yang sama
+
Time slot yang sama
```

secara bersamaan.

Requirement:

* Database transaction
* Row-level locking bila diperlukan
* Database constraint
* Server-side validation
* Berlaku sama persis untuk walk-in — admin panel tidak memiliki jalur "pintas" yang melewati pengecekan ini

Frontend validation hanya digunakan untuk UX dan **bukan** sebagai mekanisme keamanan.

### Acceptance Criteria

> Jika dua booking (dari sumber apa pun — online maupun walk-in) mencoba mengambil slot yang sama secara bersamaan, hanya satu yang dapat berhasil.

---

# 12. Booking Status

```text
PENDING
   ↓
WAITING_PAYMENT
   ↓
PAID
   ↓
CONFIRMED
   ↓
COMPLETED
```

Alternative:

```text
WAITING_PAYMENT → EXPIRED
WAITING_PAYMENT → FAILED

CONFIRMED → CANCELLED
PAID → CANCELLED
```

| Transition                | Trigger                                                        |
| -------------------------- | ---------------------------------------------------------------- |
| PENDING → WAITING_PAYMENT | Midtrans transaction dibuat untuk pembayaran awal (DP atau Full Payment, booking online) |
| WAITING_PAYMENT → PAID    | Midtrans webhook terverifikasi untuk pembayaran awal (DP atau Full Payment) |
| PAID → CONFIRMED          | Pembayaran awal berhasil (booking dianggap `CONFIRMED` walau baru DP — sisa/produk ditangani via `payment_status`, lihat Bagian 9C) |
| WAITING_PAYMENT → EXPIRED | Payment timeout                                                  |
| WAITING_PAYMENT → FAILED  | Payment gagal                                                    |
| CONFIRMED → COMPLETED     | Service selesai (ditandai admin — baik untuk online maupun walk-in, karena barber tidak dapat mengubah status sendiri). **Direkomendasikan hanya dapat dilakukan jika `payment_status = paid_full`, kecuali admin override manual (audit log) — Business Decision Required (Bagian 9C.5).** |
| CONFIRMED → CANCELLED     | Customer (online) / Admin                                        |
| → CONFIRMED (langsung, `payment_status = unpaid`) | Admin submit walk-in — tidak melalui `WAITING_PAYMENT`/Midtrans; booking langsung `CONFIRMED` tanpa pembayaran (Bagian 9A.4) |

**Catatan v1.3:** `status` booking (siklus di atas) dan `payment_status` (`unpaid`/`partial`/`paid_full`, Bagian 9C) adalah **dua dimensi terpisah**. `status` menunjukkan tahap operasional booking (dijadwalkan → dikerjakan → selesai), sementara `payment_status` menunjukkan kelengkapan pembayaran terhadap `total_amount` yang bisa berubah (karena produk) sepanjang booking berjalan.

---

# 13. Midtrans & Manual Payment

Midtrans digunakan untuk pembayaran **awal booking online** (DP atau Full Payment, sesuai pilihan customer di Bagian 9). Pembayaran lain — pelunasan sisa DP, pembayaran produk tambahan, dan seluruh transaksi walk-in — dicatat **manual** oleh Admin (lihat Bagian 9C).

### Flow — Pembayaran Awal Online (Midtrans)

```text
Create Booking
      ↓
Customer Pilih Payment Type (DP / Full)
      ↓
Create Midtrans Transaction (sejumlah DP atau Full)
      ↓
Customer Payment
      ↓
Midtrans Webhook
      ↓
Server Verification
      ↓
Update Payment Record (payment ke-1)
      ↓
Update Booking (status & payment_status)
      ↓
Confirmation
```

### Flow — Pelunasan / Pembayaran Produk (Manual, oleh Admin)

```text
Booking CONFIRMED, outstanding_amount > 0
      ↓
(Layanan berlangsung — barber tawarkan produk, opsional)
      ↓
Admin buka booking → "Catat Pembayaran"
      ↓
Admin input nominal & metode (cash / qris_static / midtrans-manual)
      ↓
Sistem buat payment record baru
      ↓
Recalculate outstanding_amount & payment_status
      ↓
(Jika outstanding = 0) Admin dapat tandai COMPLETED
```

### Critical Rules

* Nominal DP ditentukan backend.
* Customer tidak dapat mengubah nominal DP maupun harga produk.
* Frontend tidak boleh menentukan status payment.
* Payment harus diverifikasi server-side (untuk jalur Midtrans) atau dicatat dengan `recorded_by` yang jelas (untuk jalur manual).
* Webhook harus idempotent — **per payment record**, bukan per booking, karena satu booking dapat memiliki beberapa payment.
* Duplicate webhook tidak boleh membuat perubahan ganda.
* **Total seluruh payment sukses pada satu booking tidak boleh melebihi `total_amount` booking tersebut** (Bagian 9C.5).

### Payment Status (per payment record)

```text
PENDING
PAID
FAILED
EXPIRED
CANCELLED
REFUNDED
```

### Payment Method

```text
midtrans     (booking online — DP/Full di awal; opsional untuk pelunasan via link, TBD)
cash         (pelunasan & walk-in, dicatat manual oleh admin)
qris_static  (pelunasan & walk-in via QRIS statis di kasir, dicatat manual oleh admin)
```

### Payment Purpose (Baru — v1.3)

Untuk memudahkan pelaporan, setiap payment record juga dapat ditandai tujuannya:

```text
dp            (pembayaran DP awal, online)
full_payment  (pembayaran penuh service di awal, online)
pelunasan     (pelunasan sisa DP dan/atau produk, dicatat setelah layanan)
walk_in       (pembayaran walk-in, dicatat setelah layanan)
```

---

# 14. DP & Full Payment Rules

Customer online memilih salah satu di checkout:

### DP

Nominal DP dapat berupa:

* Fixed amount
* Percentage

**TBD — Owner harus menentukan mekanisme dan nominal DP.**

Booking yang belum dibayar (belum bayar DP maupun Full) akan:

```text
WAITING_PAYMENT
       ↓
     EXPIRED
```

setelah batas waktu tertentu.

Durasi hold slot juga:

**TBD**

Contoh rekomendasi: 15–30 menit.

Sisa pembayaran (setelah DP) **wajib dilunasi setelah layanan cukur selesai** (pelunasan) — dicatat oleh Admin sesuai Bagian 9C. Sistem **tidak** secara otomatis menagih sisa DP sebelum layanan dimulai; layanan tetap dapat dilaksanakan selama booking berstatus `CONFIRMED`, terlepas dari `payment_status` (`partial`).

### Full Payment

Customer membayar 100% total service di awal via Midtrans. Booking langsung `PAID`/`CONFIRMED` dengan `payment_status = paid_full` (untuk komponen service). Jika kemudian ada produk ditambahkan saat layanan, `payment_status` booking kembali menjadi `partial` hingga produk tersebut dilunasi (Bagian 9B.5 & 9C).

### Walk-in

Walk-in **tidak menggunakan skema DP maupun Full Payment di awal**. Customer tidak membayar apa pun saat booking dibuat — seluruh pembayaran (service + produk apa pun yang diambil) dicatat oleh Admin **setelah layanan selesai**, dapat menggunakan lebih dari satu metode pembayaran (Bagian 9C).

---

# 15. Schedule Management

Setiap barber memiliki:

* Working days
* Working hours
* Break time
* Day off
* Blocked time
* Existing booking (online + walk-in)

**Seluruhnya diatur oleh Admin** — barber tidak memiliki akses untuk melihat atau mengubah jadwalnya sendiri secara digital (di luar scope MVP, komunikasi jadwal ke barber tetap dilakukan secara offline/manual oleh admin, mis. lisan atau papan jadwal fisik).

Admin dapat:

* Menentukan jam kerja
* Menambahkan break
* Menentukan day off
* Memblokir slot
* Melihat booking
* Mengubah schedule
* Menginput walk-in booking langsung dari tampilan schedule barber (opsional shortcut UX)

### Important Rule

Perubahan schedule **tidak otomatis membatalkan booking yang sudah confirmed**, baik online maupun walk-in.

Admin harus menangani konflik secara manual melalui:

* Reschedule
* Cancellation
* Refund sesuai kebijakan bisnis

---

# 16. Admin Dashboard

Dashboard menampilkan:

* Today's bookings (dengan indikator sumber: online / walk-in, dan `payment_status`: unpaid/partial/paid_full)
* Upcoming bookings
* Pending payments (termasuk booking dengan `outstanding_amount` > 0 — DP belum dilunasi, produk belum dibayar, atau walk-in belum dibayar)
* Confirmed bookings
* Completed bookings
* Cancelled bookings
* Revenue (dapat difilter/dipecah berdasarkan sumber: online vs walk-in, dan berdasarkan **service vs product**)
* Active barbers
* Rasio booking online vs walk-in
* **Daftar produk terlaris (top-selling products)**
* **Daftar booking dengan pembayaran outstanding, terurut berdasarkan tanggal layanan**

Booking dapat:

* Search
* Filter
* Sort
* View detail (termasuk rincian seluruh `booking_items` dan seluruh `payments` per booking)

Filter minimal:

* Date
* Barber
* Status
* Source (online / walk-in)
* **Payment status (unpaid / partial / paid_full)**

---

# 17. Admin Management

## Booking Management (termasuk Walk-in)

Admin dapat:

* View booking (termasuk rincian item service, item produk, dan seluruh payment record)
* Filter booking
* Change booking status
* Cancel booking
* View payment
* **Tambah produk ke booking yang sedang berjalan (Bagian 9B)**
* **Catat pembayaran/pelunasan dengan metode apa pun, bisa lebih dari satu record per booking (Bagian 9C)**
* Handle schedule conflicts
* Create booking baru untuk walk-in customer (lihat Bagian 9A)
* Tandai walk-in atau online booking sebagai selesai (COMPLETED) — satu-satunya pihak yang berwenang mengubah status ini, karena barber tidak memiliki akses sistem

## Barber Management

Barber dikelola **sepenuhnya sebagai data** oleh Admin — bukan akun pengguna.

Admin dapat:

* Create barber (data profil)
* Edit barber
* Activate/deactivate barber
* Upload photo
* Set specialty
* Set schedule

Gunakan **soft delete/deactivation** agar histori booking tetap aman.

## Service Management

Admin dapat:

* Create service
* Edit service
* Set price
* Set duration
* Activate/deactivate service

Harga dan durasi selalu berasal dari backend — termasuk saat digunakan untuk booking walk-in (kecuali admin diberikan izin eksplisit untuk override harga, lihat Bagian 9A.7).

## Product Management (Baru — v1.3)

Admin dapat:

* Create product (nama, harga, foto — opsional)
* Edit product
* Activate/deactivate product

Harga produk selalu berasal dari backend saat ditambahkan ke booking (snapshot) — konsisten dengan aturan harga service. Manajemen stok **tidak termasuk MVP** (lihat Bagian 4 & Open Questions).

---

# 18. Customer Confirmation

Setelah pembayaran awal berhasil (DP atau Full Payment), customer mendapatkan:

* Booking ID
* Service
* Barber
* Date
* Time
* Total price (service)
* **Payment type (DP / Full Payment)**
* Jumlah yang sudah dibayar & estimasi sisa (jika DP)
* Payment status
* Booking status

Action:

* View booking
* Contact via WhatsApp
* Open Google Maps

Setelah layanan selesai dan pelunasan/pembayaran produk dicatat, customer (jika memiliki akun/login history) dapat melihat rincian akhir booking termasuk produk yang dibeli dan seluruh payment record.

Catatan: untuk walk-in, "confirmation" berupa struk/ringkasan yang dapat ditampilkan langsung oleh admin di tempat (di layar admin panel) setelah pembayaran akhir dicatat — tidak memerlukan halaman konfirmasi terpisah untuk customer, karena customer sudah berada secara fisik di lokasi.

---

# 19. Edge Cases

| Case                          | Expected Behavior                                  |
| ----------------------------- | ---------------------------------------------------- |
| Slot diambil customer lain    | Backend menolak booking                             |
| Payment webhook terlambat     | Tunggu webhook, jangan langsung dianggap gagal      |
| Payment expired               | Booking expired, slot dilepas                       |
| Payment failed                | Customer dapat mencoba kembali                      |
| Customer menutup payment page | Booking tetap waiting sampai expired                |
| Duplicate webhook             | Diabaikan menggunakan idempotency (per payment record) |
| Barber (data) dinonaktifkan admin | Admin menangani reschedule/cancel booking terkait |
| Schedule berubah              | Booking existing tidak otomatis dibatalkan          |
| Customer cancel               | Status cancelled, refund mengikuti business policy (termasuk refund DP yang sudah dibayar jika berlaku) |
| Admin cancel                  | Status cancelled dan slot dilepas                   |
| Service inactive              | Booking existing tetap valid                        |
| Barber inactive               | Booking existing tetap valid                        |
| Product inactive              | Booking existing yang sudah memiliki item produk tersebut tetap valid (snapshot) |
| Database failure              | Transaction rollback                                |
| Midtrans down                 | Payment awal dapat di-retry                          |
| Double-click payment          | Cegah duplicate transaction                          |
| Walk-in bentrok dengan booking online yang baru dibuat pada waktu bersamaan | Sistem menolak salah satu berdasarkan siapa yang lebih dulu mengunci slot di database (row-level locking) — bukan berdasarkan sumber (online/walk-in) |
| Admin lupa mengubah status walk-in menjadi COMPLETED | Booking tetap CONFIRMED; disarankan reminder/checklist di dashboard untuk booking hari berjalan yang belum di-mark selesai |
| Customer walk-in datang tanpa memberi nomor HP | Sistem tetap dapat menyimpan booking bila nomor HP dijadikan opsional untuk walk-in (lihat Open Questions 9A.7); nama tetap wajib untuk identifikasi minimal |
| Barber mencoba mengakses admin panel atau route walk-in/produk secara langsung (mis. lewat URL) | Ditolak sepenuhnya — barber tidak memiliki akun/kredensial apa pun dalam sistem, sehingga tidak ada request yang dapat terautentikasi sebagai barber |
| **Customer DP tidak kembali untuk pelunasan (booking dibuat online tapi tidak datang, atau datang tapi pergi tanpa melunasi)** | **Booking tetap `partial`; SOP internal menentukan tindakan (reminder WhatsApp, blokir booking berikutnya sampai lunas, dsb.) — Business Decision Required** |
| **Admin menambahkan produk ke booking yang total pembayarannya sudah `paid_full`** | **`payment_status` otomatis kembali menjadi `partial` karena `outstanding_amount` bertambah — dashboard menampilkan booking ini sebagai perlu pembayaran tambahan** |
| **Total nominal payment yang diinput admin melebihi `outstanding_amount` booking** | **Sistem menolak/memberi peringatan sebelum menyimpan — mencegah data pembayaran yang tidak konsisten dengan tagihan** |

---

# 20. Database

Minimal tabel:

```text
users
barbers
services
products
bookings
booking_items
schedules
blocked_slots
payments
reviews
```

### Perubahan Skema — v1.3

**`products`** *(baru)*
- `id`, `name`, `price`, `photo` (opsional), `is_active`, timestamps.
- Tidak memiliki kolom stok pada MVP (lihat Open Questions).

**`booking_items`**
- Tambahan kolom `item_type` — enum(`service`, `product`).
- `service_id` — nullable, diisi bila `item_type = service`.
- `product_id` — nullable, diisi bila `item_type = product`.
- `qty` — jumlah unit (relevan terutama untuk produk; service default 1).
- `service_name_snapshot` / `product_name_snapshot`, `price_snapshot`, `duration_snapshot` (duration hanya relevan untuk service, null untuk product).
- `added_by` — FK → `users.id` (admin yang menambahkan item ini; untuk service awal biasanya sama dengan pembuat booking/`system` untuk booking online, untuk product selalu admin yang menambahkan saat upselling).

**`bookings`**
- Tambahan kolom `payment_type` — enum(`dp`, `full`, `pay_later`) — `dp`/`full` untuk booking online sesuai pilihan customer di checkout; `pay_later` untuk walk-in (tidak ada pembayaran di awal).
- Tambahan kolom `total_amount` — dihitung dari jumlah seluruh `booking_items` aktif (service + product), bersifat dinamis selama booking belum `COMPLETED`.
- Tambahan kolom `outstanding_amount` — `total_amount` dikurangi jumlah seluruh `payments` sukses; dihitung ulang setiap kali ada perubahan item atau payment.
- Tambahan kolom `payment_status` — enum(`unpaid`, `partial`, `paid_full`), derived dari `outstanding_amount` vs `total_amount` (dapat disimpan sebagai kolom ter-cache untuk kemudahan query, di-update pada setiap perubahan terkait).

**`payments`**
- **Relasi berubah dari one-to-one menjadi one-to-many terhadap `bookings`** — satu booking dapat memiliki banyak record `payments`.
- Kolom: `id`, `booking_id` (FK), `amount`, `method` (`midtrans`/`cash`/`qris_static`), `purpose` (`dp`/`full_payment`/`pelunasan`/`walk_in`), `status` (`pending`/`paid`/`failed`/`expired`/`cancelled`/`refunded`), `midtrans_transaction_id` (nullable, hanya untuk method `midtrans`), `recorded_by` (FK → `users.id`, nullable — admin yang mencatat pembayaran manual), timestamps.

**`users`**
- Kolom `role` menjadi enum(`user`, `admin`) — **hanya 2 nilai**, tidak lagi ada `barber`. (tetap dari v1.2)

**`barbers`**
- **Tidak memiliki FK ke `users`.** Barber adalah entitas data mandiri (`id`, `name`, `photo`, `specialty`, `is_active`, dst.) tanpa relasi ke akun login mana pun. (tetap dari v1.2)

### Important Relationships

```text
User (role: user)
 └── Bookings

Barber (data murni, tanpa akun)
 ├── Bookings (online + walk-in)
 ├── Schedules
 └── Blocked Slots

Booking
 ├── Booking Items (service & product, banyak)
 └── Payments (banyak, metode & tujuan berbeda-beda)

Service
 └── Booking Items (item_type = service)

Product
 └── Booking Items (item_type = product)
```

### Kolom Tambahan pada `bookings` (dari v1.1/v1.2, tetap berlaku)

| Kolom | Tipe | Keterangan |
|---|---|---|
| `source` | enum(`online`, `walk_in`) | Menandai asal booking |
| `created_by` | FK → `users.id` (nullable) | **Admin** yang menginput, khusus untuk booking walk-in — selalu admin, tidak pernah barber |

`customer_id` pada tabel `bookings` untuk walk-in dapat bersifat **nullable**, dengan `walk_in_customer_name` & `walk_in_customer_phone` disimpan langsung di tabel `bookings` tanpa relasi ke `users` (rekomendasi untuk kesederhanaan MVP, karena walk-in tidak membutuhkan akun/login — konsisten dengan prinsip yang sama seperti barber).

### Booking Snapshot

`booking_items` harus menyimpan snapshot:

```text
service_name_snapshot / product_name_snapshot
price_snapshot
duration_snapshot   (hanya untuk service)
```

Tujuannya agar histori booking tidak berubah ketika service/product master diubah. Berlaku sama untuk booking online maupun walk-in, dan untuk item service maupun product.

---

# 21. Backend Requirements

### Public

```text
GET  /
GET  /services
GET  /barbers
GET  /availability
POST /bookings
GET  /bookings/{id}
POST /payments
```

### Midtrans

```text
POST /midtrans/webhook
```

### Admin

```text
/admin/dashboard
/admin/barbers
/admin/services
/admin/products
/admin/schedules
/admin/bookings
/admin/bookings/walk-in
/admin/bookings/{id}/items       (tambah booking_item — service tambahan / product upselling)
/admin/bookings/{id}/payments    (catat payment record baru — pelunasan, pembayaran produk, walk-in)
/admin/payments
```

**Catatan:** route `/barber/*` (schedule, bookings) yang sebelumnya direncanakan di v1.1 **dihapus** — tidak relevan lagi karena barber tidak memiliki akun/login. Seluruh data terkait barber diakses melalui `/admin/*`. Hal yang sama berlaku untuk produk: tidak ada route `/barber/products/*` — penambahan produk selalu melalui `/admin/bookings/{id}/items`.

Semua route admin wajib dilindungi middleware role `admin`. Route publik/`user` dilindungi middleware role `user` bila memerlukan autentikasi (mis. booking history). Route walk-in tetap wajib melalui availability engine dan double-booking lock yang sama dengan booking online — tidak ada bypass.

---

# 22. Security Requirements

Wajib menerapkan:

* Laravel Authentication
* **RBAC sederhana — hanya 2 role: `user`, `admin`**
* Middleware authorization
* CSRF protection
* Server-side validation
* XSS prevention
* SQL injection prevention
* Midtrans webhook verification
* Rate limiting
* Database transaction
* Payment idempotency (per payment record)

### Critical Rule

> Jangan pernah mempercayai harga, DP, availability, atau payment status dari frontend — termasuk dari form input walk-in, form tambah produk, dan form pencatatan pelunasan di admin panel. Harga dan durasi tetap diambil dari master data backend kecuali admin diberikan izin eksplisit untuk override, yang harus tercatat (audit log) siapa yang melakukannya. **Nominal payment yang diinput admin wajib divalidasi backend agar tidak melebihi `outstanding_amount` booking.**

Semua data kritis harus diverifikasi oleh backend. Dengan hanya 2 role, permukaan serangan otorisasi (authorization surface) menjadi lebih kecil dan lebih mudah diuji dibanding skema 3-role sebelumnya.

---

# 23. Non-Functional Requirements

### Performance

Target:

> Public page dan booking page < 3 detik pada koneksi normal. Form input walk-in, form tambah produk, dan form catat pembayaran di admin panel harus dapat diselesaikan admin dalam waktu singkat (idealnya < 1 menit per input) agar tidak menghambat operasional kasir saat ramai — ini makin penting karena **hanya admin** yang dapat melakukan input ini (tidak ada barber yang bisa membantu meng-input).

### Reliability

Booking dan payment harus konsisten.

Tidak boleh terjadi:

```text
Booking = PAID
Payment = FAILED
```

atau kondisi inkonsisten lainnya — berlaku untuk kedua sumber booking. Secara khusus, **jumlah seluruh payment sukses pada satu booking tidak boleh pernah melebihi `total_amount` booking tersebut**, dan `outstanding_amount`/`payment_status` harus selalu ter-recalculate secara atomik setiap kali ada penambahan item atau payment baru.

### Maintainability

Gunakan:

* Laravel MVC
* Service Layer untuk business logic kompleks (termasuk logic bersama antara booking online & walk-in, serta logic kalkulasi `total_amount`/`outstanding_amount`/`payment_status` yang dipakai bersama di seluruh titik penambahan item/payment, agar tidak terjadi duplikasi/divergensi logic)
* Reusable Blade Components
* Clean database relationships
* **RBAC minimal (2 role)** menyederhanakan testing authorization dibanding skema sebelumnya

---

# 24. SEO & Branding

Public website harus memiliki:

* SEO-friendly URL
* Meta title
* Meta description
* Open Graph
* Semantic HTML
* Alt text
* Optimized images
* Lazy loading

### Branding

Gunakan:

* Logo resmi
* Foto barber
* Portfolio
* Brand colors
* Typography
* Tone of voice

sebagai identitas Kref Barber.

Instagram `@krefbraber` digunakan sebagai referensi brand personality jika aset tersedia.

**Jangan mengarang informasi brand.**

---

# 25. MVP Scope

| Feature                                                | Priority |
| ------------------------------------------------------- | -------- |
| Landing Page                                             | P0       |
| Services                                                 | P0       |
| Barbers (data publik, dikelola admin)                    | P0       |
| Booking Flow (Online)                                    | P0       |
| **Payment Type Selection (DP / Full Payment)**            | **P0**   |
| Walk-in Booking Input (Admin only, tanpa pembayaran awal) | P0       |
| **Product Upselling (Admin menambah produk ke booking)**  | **P0**   |
| **Pelunasan / Multiple Payment per Booking**               | **P0**   |
| Availability Engine (shared online + walk-in)            | P0       |
| Double Booking Prevention (shared online + walk-in)      | P0       |
| Midtrans (pembayaran awal online)                         | P0       |
| Payment Webhook                                          | P0       |
| Booking State Machine                                    | P0       |
| Admin Booking Management                                 | P0       |
| Admin Barber Management (data, bukan akun)                | P0       |
| Service Management                                       | P0       |
| **Product Management (master data)**                      | **P0**   |
| Schedule Management (oleh Admin)                          | P0       |
| Booking Confirmation                                     | P0       |
| Customer Login/History                                    | P1       |
| Admin Analytics (termasuk breakdown online vs walk-in, service vs product) | P1 |
| Gallery                                                  | P1       |
| SEO                                                      | P1       |
| **Manajemen stok produk**                                  | **P2 (di luar scope awal)** |
| Reviews                                                  | P2       |
| Reminder (termasuk reminder pelunasan/outstanding)          | P2       |
| Reschedule                                               | P2       |
| Kios self-check-in walk-in oleh customer                  | P2 (di luar scope awal) |
| ~~Barber dapat input walk-in sendiri~~                    | **Dihapus (v1.2)** — barber tidak memiliki akses sistem |
| ~~Akun login barber~~                                     | **Dihapus (v1.2)** — di luar scope produk saat ini |

**Alasan Walk-in Booking Input tetap P0:** Tanpa fitur ini, availability engine online tidak akan pernah akurat — risiko double booking online vs walk-in menjadi nyata sejak hari pertama platform digunakan. Karena **hanya admin** yang dapat menginput, penting memastikan UX form ini secepat dan sesimpel mungkin agar tidak menghambat operasional saat admin merangkap tugas lain.

**Alasan Product Upselling & Multiple Payment tetap P0:** Tanpa fitur ini, revenue produk berpotensi tidak tercatat di sistem sama sekali (kembali ke pencatatan manual di luar sistem), dan skema DP + pelunasan tidak dapat direpresentasikan secara akurat — dua hal yang langsung berdampak pada akurasi dashboard revenue sejak hari pertama.

### MVP Core Flow — Online

```text
Service
  ↓
Barber
  ↓
Date
  ↓
Time
  ↓
Customer Information
  ↓
Booking Summary
  ↓
Pilih Payment Type (DP / Full)
  ↓
DP/Full Payment via Midtrans
  ↓
Confirmation
  ↓
(Hari-H) Layanan + opsional produk (ditambahkan Admin)
  ↓
(Jika DP atau ada produk) Pelunasan oleh Admin
  ↓
COMPLETED
```

### MVP Core Flow — Walk-in (Admin Only)

```text
Admin buka menu Walk-in
  ↓
Pilih Barber
  ↓
Sistem tampilkan slot kosong saat ini
  ↓
Pilih Service
  ↓
Input nama (& HP opsional)
  ↓
Booking langsung CONFIRMED, payment_status = unpaid (tanpa input metode pembayaran)
  ↓
Slot terkunci untuk online
  ↓
Layanan berlangsung + opsional produk (ditambahkan Admin)
  ↓
Admin catat pembayaran penuh (service + produk, metode bebas) setelah layanan selesai
  ↓
COMPLETED
```

Admin:

```text
Barber (data)
  ↓
Service
  ↓
Product
  ↓
Schedule
  ↓
Booking (online & walk-in)
  ↓
Booking Items (tambah produk)
  ↓
Payment (pelunasan / multiple payment)
```

---

# 26. Future Roadmap

## Phase 2

* Customer reviews
* Promo code
* WhatsApp reminder (termasuk reminder pelunasan bagi booking DP yang belum lunas)
* Self-service reschedule
* Advanced analytics
* **Manajemen stok produk (pengurangan stok otomatis saat produk ditambahkan ke booking)**
* Kios/self-check-in digital untuk walk-in (customer input data sendiri via tablet di lokasi, mengurangi beban input admin)
* **Evaluasi apakah barber perlu diberikan akses terbatas (mis. lihat jadwal via link/PWA sederhana tanpa login penuh, atau bahkan input produk yang ditawarkannya sendiri tanpa login penuh) — dapat dipertimbangkan jika volume operasional bertambah dan admin tunggal menjadi bottleneck**
* Integrasi QRIS statis dengan rekonsiliasi otomatis (mengurangi pencatatan manual admin)

## Phase 3

* Membership
* Loyalty point
* Multi-outlet
* Product marketplace (customer beli produk mandiri via website)
* Customer segmentation
* Advanced CRM
* Akun login penuh untuk barber (jika bisnis berkembang ke arah yang membutuhkan barber mengelola jadwalnya sendiri, mis. multi-outlet dengan banyak barber)

Fitur future harus dievaluasi berdasarkan data penggunaan MVP.

---

# 27. Success Metrics

Track:

* Booking Conversion Rate
* Booking Completion Rate
* Payment Success Rate
* Cancellation Rate
* Average Booking Time
* Manual WhatsApp Booking Reduction
* Repeat Booking Rate
* Slot Utilization Rate
* Online vs Walk-in Ratio
* **DP vs Full Payment Ratio (booking online)**
* **Product Attach Rate** — persentase booking yang memiliki minimal 1 item produk
* **Average Product Revenue per Booking**
* **Pelunasan Completion Rate** — persentase booking DP yang berhasil dilunasi vs yang outstanding/walk-out
* Walk-in Input Time — makin krusial dipantau karena hanya admin yang dapat melakukan input ini; jika waktu input terlalu lama, ini indikasi form perlu disederhanakan atau dibutuhkan lebih dari satu perangkat admin di jam ramai.

---

# 28. Risks

| Risk                                  | Mitigation                                |
| --------------------------------------- | -------------------------------------------- |
| Double booking                          | Transaction + database constraint            |
| Payment tidak sinkron                   | Server-side Midtrans verification (per payment record) |
| Nominal DP belum ditentukan             | Business decision sebelum development        |
| Customer tetap menggunakan WhatsApp     | Promosi website melalui Instagram            |
| Barber schedule tidak update             | SOP internal — admin bertanggung jawab penuh atas seluruh update jadwal barber |
| Midtrans/hosting downtime               | Error handling + retry                       |
| Admin lupa/malas input walk-in ke sistem | Form input walk-in dibuat sesederhana & secepat mungkin (Bagian 9A.4); SOP internal mewajibkan setiap walk-in diinput sebelum layanan dimulai |
| Availability online jadi tidak akurat jika walk-in tidak konsisten diinput real-time | Wajibkan input walk-in dilakukan di awal kunjungan, bukan retroaktif |
| Admin menjadi single point of failure — bila admin tidak tersedia (sakit, sibuk), tidak ada barber yang bisa menginput walk-in/produk/pembayaran sebagai cadangan | Pastikan minimal ada 2 akun admin (mis. owner + staff kasir) agar operasional tidak berhenti saat satu admin berhalangan; ini SOP bisnis, bukan batasan teknis sistem |
| **Customer DP atau walk-in tidak kembali/pergi tanpa melunasi sisa pembayaran (bad debt)** | **SOP internal: reminder WhatsApp otomatis (Phase 2), kebijakan blokir booking berikutnya sampai lunas, atau kebijakan write-off — Business Decision Required (Bagian 29)** |
| **Barber menawarkan produk tapi Admin lupa mencatatnya ke sistem (revenue produk hilang dari laporan)** | **SOP internal: checklist di kasir "cek penambahan produk sebelum layanan selesai"; UX tambah produk dibuat secepat mungkin agar tidak menghambat alur kasir** |
| **Kesalahan input nominal payment oleh admin menyebabkan `outstanding_amount` tidak akurat** | **Validasi backend mencegah total payment melebihi `total_amount`; tampilkan `outstanding_amount` secara jelas di layar sebelum admin submit** |
| **Reconciliation menjadi rumit karena satu booking bisa punya banyak payment record dengan metode berbeda** | **Dashboard payment detail per booking menampilkan seluruh payment record secara kronologis dengan metode & purpose masing-masing, memudahkan audit** |

---

# 29. Open Questions

Sebelum development dimulai, owner perlu menentukan:

1. Nominal/skema DP?
2. Berapa lama slot di-hold?
3. Customer wajib login atau boleh guest?
4. Bagaimana kebijakan refund (termasuk refund DP jika booking dibatalkan)?
5. Harga setiap service?
6. Jam operasional?
7. Alamat outlet?
8. Nama dan foto barber?
9. Durasi setiap service?
10. Logo dan brand assets tersedia?
11. Confirmation melalui web, email, atau WhatsApp?
12. Bagaimana prosedur ketika booking perlu dibatalkan (mis. barber berhalangan mendadak)?
13. Apakah admin boleh memberi diskon/penyesuaian harga manual untuk walk-in atau produk?
14. Apakah nomor HP wajib diisi untuk walk-in, atau boleh dikosongkan?
15. Apakah perlu buffer slot khusus walk-in di jam-jam ramai?
16. Berapa jumlah akun admin yang dibutuhkan (satu untuk owner, tambahan untuk staff kasir/shift lain)?
17. Bagaimana barber mengetahui jadwalnya sehari-hari jika tidak memiliki akses sistem? (mis. tetap dikomunikasikan admin secara lisan/WA grup internal, dicetak, atau ditampilkan di layar bersama di lokasi)
18. **Apakah pelunasan wajib dilakukan sebelum booking dapat ditandai `COMPLETED`, atau admin diperbolehkan override manual dengan status outstanding tetap berjalan?**
19. **Bagaimana kebijakan bila customer (online DP maupun walk-in) tidak melunasi sisa pembayaran (walk-out/bad debt)?**
20. **Apakah produk yang ditawarkan barber memerlukan manajemen stok pada MVP, atau cukup daftar harga tanpa tracking stok?**
21. **Apakah pelunasan/QRIS statis di lokasi perlu terintegrasi otomatis (mis. webhook dari mesin QRIS) atau tetap dicatat manual oleh admin sepenuhnya untuk MVP?**
22. **Apakah booking Full Payment yang kemudian menambah produk perlu notifikasi otomatis ke customer bahwa ada tagihan tambahan, atau cukup ditagih langsung di lokasi oleh admin?**
23. **Apakah komisi/insentif barber atas penjualan produk perlu dilacak di sistem (mis. `booking_items.added_for_barber` untuk keperluan komisi), atau ini murni ditangani secara terpisah di luar sistem?**

Semua informasi yang belum tersedia dianggap **TBD**.

---

# 30. Critical Business Rules

1. **Harga service berasal dari backend.**
2. **Nominal DP berasal dari backend.**
3. **Payment hanya dianggap valid setelah Midtrans terverifikasi server-side (untuk pembayaran awal booking online) atau tercatat dengan `recorded_by` yang jelas (untuk pembayaran manual).**
4. **Satu barber tidak boleh memiliki dua booking aktif pada slot yang sama — berlaku sama untuk online maupun walk-in, tanpa pengecualian.**
5. **Expired/cancelled booking melepaskan slot.**
6. **Booking existing tidak berubah ketika master service/product/barber berubah (snapshot).**
7. **Data harga dan durasi disimpan sebagai snapshot pada booking, untuk item service maupun product.**
8. **Barber/service/product yang dinonaktifkan tidak menghapus histori booking.**
9. **Schedule conflict tidak boleh otomatis membatalkan confirmed booking.**
10. **Webhook Midtrans harus idempotent per payment record.**
11. **Booking walk-in wajib melalui availability engine dan mekanisme locking yang sama dengan booking online — tidak ada jalur pintas yang melewati pengecekan double booking.**
12. **Setiap booking wajib memiliki penanda `source` (online/walk_in) untuk keperluan pelaporan dan audit.**
13. **Sistem hanya memiliki dua role: `user` dan `admin`. Barber tidak pernah menjadi principal otentikasi dalam sistem — seluruh operasi terkait barber (jadwal, status, walk-in, produk) dieksekusi atas nama admin.**
14. **Total tagihan booking (`total_amount`) dihitung dari seluruh `booking_items` aktif (service + product); `payment_status` selalu dihitung dari perbandingan `outstanding_amount` terhadap `total_amount` saat itu, bukan nilai statis.**
15. **Satu booking dapat memiliki banyak record `payments` dengan metode berbeda; backend wajib memvalidasi bahwa jumlah seluruh payment sukses tidak pernah melebihi `total_amount` booking tersebut.**
16. **Produk hanya dapat ditambahkan ke booking oleh Admin, menggunakan harga master data (snapshot) — tidak ada input harga bebas kecuali override eksplisit yang tercatat di audit log.**
17. **Walk-in tidak memerlukan pembayaran apa pun saat booking dibuat; seluruh pembayaran (service + produk) dicatat setelah layanan selesai.**

---

# 31. Definition of Done — MVP

MVP dianggap siap digunakan apabila:

* [ ] Customer dapat melihat service.
* [ ] Customer dapat melihat barber.
* [ ] Customer dapat melihat availability.
* [ ] Customer dapat memilih date & time.
* [ ] Customer dapat membuat booking.
* [ ] **Customer dapat memilih payment type (DP atau Full Payment) saat checkout.**
* [ ] Sistem mencegah double booking (termasuk antara online dan walk-in).
* [ ] Customer dapat membayar (DP atau Full) melalui Midtrans.
* [ ] Webhook Midtrans berhasil diverifikasi per payment record.
* [ ] Booking status berubah secara otomatis.
* [ ] Expired payment melepaskan slot.
* [ ] Admin dapat mengelola data barber (tanpa akun login barber).
* [ ] Admin dapat mengelola service.
* [ ] **Admin dapat mengelola produk (master data upselling).**
* [ ] Admin dapat mengelola schedule.
* [ ] Admin dapat mengelola booking.
* [ ] Admin dapat menginput booking walk-in **tanpa perlu memilih metode pembayaran**, dan slot langsung terkunci secara real-time terhadap booking online.
* [ ] **Admin dapat menambahkan produk ke booking (online maupun walk-in) yang sedang berjalan, dengan harga dari master data.**
* [ ] **Admin dapat mencatat pelunasan/pembayaran tambahan dengan lebih dari satu metode pembayaran pada satu booking, dan sistem menghitung `outstanding_amount`/`payment_status` secara akurat.**
* [ ] **Sistem mencegah total payment melebihi `total_amount` booking.**
* [ ] Dashboard dapat menampilkan/filter booking berdasarkan source (online/walk-in) dan payment status (unpaid/partial/paid_full).
* [ ] Admin dapat melihat seluruh payment record per booking.
* [ ] Customer mendapatkan booking confirmation.
* [ ] **RBAC 2-role (`user`, `admin`) telah diuji — dipastikan tidak ada jalur akses bagi "barber" karena role tersebut tidak ada dalam sistem, termasuk untuk fitur tambah produk.**
* [ ] Critical edge cases telah diuji (termasuk skenario konflik online vs walk-in pada Bagian 19, dan skenario multiple payment/pelunasan pada Bagian 9C).

---

# 32. Final Product Definition

> **Kref Barber Online Booking** adalah platform digital resmi Kref Barber yang menggabungkan **brand experience, barber availability, appointment booking (online & walk-in), schedule management, product upselling, dan flexible payment (DP/Full Payment + multiple payment method)** dalam satu sistem — dikelola oleh **Admin**, digunakan oleh **User (Customer)**.

Core experience (Customer Online):

```text
DISCOVER
   ↓
EXPLORE
   ↓
CHOOSE BARBERMAN & TIME
   ↓
BOOK
   ↓
CHOOSE PAYMENT TYPE (DP / FULL)
   ↓
PAY
   ↓
CONFIRM
   ↓
VISIT
   ↓
(OPSIONAL: PRODUK DITAWARKAN BARBER, DIINPUT ADMIN)
   ↓
PELUNASAN (JIKA DP / ADA PRODUK)
```

Core experience (Walk-in, dijalankan Admin):

```text
CUSTOMER DATANG LANGSUNG
   ↓
ADMIN INPUT KE SISTEM (nama, barber, service) — TANPA PEMBAYARAN
   ↓
SLOT TERKUNCI (real-time, sinkron dengan online)
   ↓
LAYANAN DIBERIKAN (OPSIONAL: PRODUK DITAWARKAN BARBER, DIINPUT ADMIN)
   ↓
ADMIN CATAT PEMBAYARAN PENUH (service + produk, metode pembayaran)
   ↓
ADMIN TANDAI SELESAI
```

Prioritas produk:

**Brand → Trust → Booking (Online & Walk-in) → Flexible Payment (DP/Full + Multiple Method) → Product Upselling → Operational Efficiency**

**Model peran (v1.2, tetap berlaku di v1.3):** Sistem dirancang seramping mungkin dari sisi otorisasi — **User** memesan dan memilih tipe pembayaran, **Admin** mengoperasikan segalanya (termasuk data barber, walk-in, produk, dan seluruh pencatatan pembayaran/pelunasan). Ini menyederhanakan development MVP secara signifikan dibanding skema 3-role sebelumnya, dengan trade-off bahwa seluruh beban operasional lokasi — termasuk kasir dan upselling — bertumpu pada admin yang bertugas.