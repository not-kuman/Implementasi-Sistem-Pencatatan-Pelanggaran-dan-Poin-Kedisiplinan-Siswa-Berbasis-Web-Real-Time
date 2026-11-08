# SISTEM INFORMASI PENCATATAN PELANGGARAN DAN POIN KEDISIPLINAN SISWA BERBASIS WEB REAL-TIME
## SMA KARTIKA 1-2 MEDAN

Sistem Informasi Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa adalah aplikasi web *full-stack* berbasis *real-time* yang dirancang secara khusus untuk mengatasi inefisiensi pencatatan kedisiplinan konvensional (buku kasus manual dan arsip kertas). Aplikasi ini dikembangkan merujuk langsung pada penelitian:

> **"Implementasi Sistem Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa Berbasis Web Real-Time"**  
> Penulis: Arsyad Sadewa, Bagas Prasetyo, Niken Ayu Resdiana  
> Konteks Institusi: SMA Kartika 1-2 Medan (Yayasan Kartika Jaya Cabang I Bukit Barisan)

Sistem ini telah melewati pengujian fungsionalitas menggunakan metode **Black Box Testing** (keseluruhan skenario valid) serta evaluasi ergonomi dan kebergunaan antarmuka menggunakan instrumen **System Usability Scale (SUS)** dengan skor rata-rata **70,5** (Kategori *"Baik"*).

---

## 🌐 LIVE DEMO & AKSES ONLINE

Aplikasi ini dapat diakses dan diuji secara online melalui tautan berikut:

- 🔗 **GitHub Pages (Live Interactive Web):**  
  [https://not-kuman.github.io/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time/](https://not-kuman.github.io/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time/)
- 📦 **Repositori GitHub:**  
  [https://github.com/not-kuman/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time.git](https://github.com/not-kuman/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time.git)

> **Catatan Penggunaan Online (GitHub Pages):**  
> Versi GitHub Pages telah dilengkapi dengan *in-browser interactive engine* yang memuat basis data seeder secara mandiri. Pengguna dapat langsung menguji login Administrator, Guru BK (input pelanggaran, kalkulasi poin real-time, simulasi gateway WhatsApp), Wali Siswa (buku kendali & indikator sanksi), serta cetak laporan resmi ber-kop surat tanpa instalasi server tambahan!

---

## 1. TEKNOLOGI YANG DIGUNAKAN (TECH STACK)

Aplikasi dibangun tanpa menggunakan framework PHP berat (seperti Laravel/CodeIgniter) maupun framework JavaScript SPA (React/Vue/Next), melainkan menggunakan fondasi murni yang ringan, tangguh, dan dapat dijalankan langsung di lingkungan XAMPP:

- **Backend:** PHP Native 8.x (Berorientasi objek dengan PDO & prepared statements).
- **Basis Data:** MySQL / MariaDB (Relasional dengan InnoDB engine, Foreign Keys, dan Atomic Transactions).
- **Frontend:** HTML5, CSS3 Modern, Vanilla JavaScript (ES6+).
- **Asynchronous Communication:** Modern AJAX Fetch API dengan proteksi CSRF.
- **Visualisasi Data:** Chart.js (Grafik batang sebaran kategori dan grafik garis tren bulanan).
- **Ikonografi & Desain:** Lucide SVG Icons, Typography Plus Jakarta Sans & JetBrains Mono.
- **Integrasi Komunikasi:** WhatsApp Gateway (Fonnte / Wablas / Ruanggwa) dengan fitur **Built-in DEMO Simulator**.
- **Server Lingkungan:** XAMPP (Apache HTTP Server & MySQL Daemon).

---

## 2. PANDUAN DESAIN (ANTI AI SLOP & SWISS ENTERPRISE UI)

Desain antarmuka dirancang dengan standar produk perangkat lunak administrasi sekolah profesional:
- **Editorial Dashboard & Swiss Minimalist Layout:** Mengedepankan hierarki visual, whitespace terukur, dan tipografi berkelas.
- **Palet Warna Terkalibrasi:**
  - *Primary Navy:* `#172B3A`
  - *Secondary Slate:* `#64748B`
  - *Warm Background:* `#F7F7F4`
  - *Surface White:* `#FFFFFF`
  - *Accent Teal:* `#397C78`
  - *Soft Amber (Peringatan):* `#D6A34D`
  - *Muted Red (Bahaya):* `#C66B6B`
  - *Border Gray:* `#E5E7E6`
- **Integrasi Peta Digital Sekolah:** Halaman *login* dilengkapi peta koordinat satelit geospasial lokasi SMA Kartika 1-2 Medan (`3.5658° N, 98.6872° E`).
- **Umpan Balik Pengguna:** Menggunakan sistem *Toast Notifications* dan *Confirmation Dialogs* non-intrusif (bukan alert bawaan browser).

---

## 3. ARSITEKTUR TIGA ROLE PENGGUNA (RBAC)

Sistem mengimplementasikan pemisahan hak akses berbasis peran secara ketat (*Role-Based Access Control*), baik pada tampilan antarmuka maupun validasi di setiap *endpoint* API:

### A. ADMINISTRATOR (Akses Penuh Manajemen Master)
1. **Dashboard Eksekutif:** Ringkasan metrik real-time, grafik batang kategori pelanggaran terbanyak, grafik garis fluktuasi bulanan, daftar 5 siswa poin tertinggi, dan linimasa aktivitas terkini.
2. **Manajemen Siswa (`/admin/students.php`):** CRUD siswa, filter kelas, pencarian NIS/nama, profil detail, dan ekspor data ke CSV.
3. **Manajemen Guru & BK (`/admin/teachers.php`):** CRUD guru, NIP, jabatan, nomor kontak, serta pembuatan akun portal.
4. **Manajemen Rombel / Kelas (`/admin/classes.php`):** CRUD kelas, penetapan guru wali kelas, dan statistik rombel.
5. **Manajemen Wali Siswa (`/admin/parents.php`):** Tautkan wali dengan anak, nomor WhatsApp resmi, dan pembuatan akun login.
6. **Manajemen Kategori Pelanggaran (`/admin/categories.php`):** Penambahan dan pengeditan bobot poin pelanggaran (Ringan, Sedang, Berat).
7. **Konfigurasi Ketentuan Sanksi (`/admin/sanctions.php`):** Pengaturan ambang batas poin (min-max) dan matriks sanksi pembinaan.
8. **Riwayat Pelanggaran Lengkap (`/admin/violations.php`):** Audit seluruh insiden, filter rentang tanggal, cetak berkas, kirim ulang WhatsApp, serta penghapusan catatan yang secara atomik mengurangi poin siswa.
9. **Laporan & Rekapitulasi (`/admin/reports.php`):** Penyusunan berkas resmi dengan **Kop Surat SMA Kartika 1-2 Medan** siap cetak (PDF/Print) dan ekspor CSV.
10. **Manajemen Akun Pengguna (`/admin/users.php`):** Pengendalian status keaktifan, reset kata sandi, dan penambahan admin.
11. **Pengaturan Sistem & WhatsApp API (`/admin/settings.php`):** Pengaturan identitas institusi, koordinat peta, dan konfigurasi API gateway WhatsApp.

### B. DEWAN GURU & TIM BK (Akses Operasional Lapangan)
1. **Dashboard Guru (`/teacher/dashboard.php`):** Statistik personal catatan pelanggaran yang dilaporkan, jumlah bulan berjalan, dan jalan pintas input cepat.
2. **Pencatatan Pelanggaran (`/teacher/add_violation.php`):**
   - Pencarian siswa real-time (*autocomplete debounced*).
   - Penampilan identitas siswa & akumulasi poin saat ini.
   - Pilihan kategori pelanggaran berjenjang (*dropdown categorized*).
   - Kalkulasi otomatis bobot poin dan proyeksi status sanksi baru.
   - Transaksi atomik basis data (`BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK`) untuk mencegah duplikasi masukan.
   - Dialog konfirmasi & modal sukses dengan tombol cepat **"Kirim Notifikasi WhatsApp Sekarang"**.
3. **Riwayat Pencatatan Saya (`/teacher/history.php`):** Daftar seluruh insiden yang dicatat oleh guru bersangkutan dan status log pengiriman WhatsApp.
4. **Direktori Siswa (`/teacher/students.php`):** Pencarian profil kedisiplinan siswa (bersifat *read-only*).

### C. WALI SISWA (Akses Transparan & Read-Only)
1. **Dashboard Wali Siswa (`/parent/dashboard.php`):** Kartu identitas anak, akumulasi poin kedisiplinan, meteran visual status (*Safe / Warning / Danger*), dan riwayat insiden anak.
2. **Profil Siswa & Tata Tertib (`/parent/profile.php`):** Biodata lengkap anak dan pedoman formal matriks pembinaan sekolah.
3. **Riwayat Pelanggaran Anak (`/parent/history.php`):** Tabel kronologis insiden pelanggaran anak yang bersangkutan (akses terisolasi, tidak dapat melihat data siswa lain).

---

## 4. TEMPLATE NOTIFIKASI WHATSAPP GATEWAY

Sistem mengintegrasikan pengiriman notifikasi WhatsApp ke nomor orang tua/wali dengan format teks resmi sesuai standar penelitian:

```text
Yth. Bapak/Ibu Wali Siswa,

Kami informasikan bahwa siswa [Nama Siswa] dari kelas [Kelas] telah tercatat melakukan pelanggaran sebagai berikut:

Jenis Pelanggaran: [Nama Pelanggaran]
Tanggal: [Tanggal]
Poin Pelanggaran: [Poin]
Total Akumulasi Poin: [Total Poin]

Mohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.

Terima kasih.
SMA Kartika 1-2
```

Sistem menyediakan **Mode Simulasi DEMO** yang aktif secara default untuk lingkungan lokal tanpa memerlukan gateway berbayar. Seluruh data transmisi dan respon disimulasikan secara valid dan dicatat di tabel `notifications`.

---

## 5. AKUN DEMO PENGUJIAN (KREDENSIAL DEFAULT)

Semua akun telah dipersiapkan dengan kata sandi terenkripsi `password_hash()` BCRYPT. Pada halaman login (`login.php`), tersedia juga **tombol pintas klik satu kali** untuk langsung mengisi kredensial:

| Peran (Role) | Username | Password | Keterangan Akun |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin123` | Akses penuh seluruh master data, pengaturan sistem & laporan |
| **Guru / BK** | `guru1` | `guru123` | Drs. Hendra Gunawan, M.Pd (Koordinator BK) |
| **Guru / Kesiswaan** | `guru2` | `guru123` | Siti Aminah, S.Pd (Guru BK) |
| **Wali Siswa** | `wali_farhan` | `wali123` | Bpk. Joko Susilo (Wali dari M. Farhan Pratama, X MIPA 1) |
| **Wali Siswa** | `wali_dimas` | `wali123` | Bpk. Agus Setiawan (Wali dari Dimas Arya Nugraha, XI IPS 1) |

---

## 6. STRUKTUR DIREKTORI PROYEK

```text
school-discipline/
├── assets/
│   ├── css/
│   │   ├── app.css              # Tata letak, tipografi, grid & warna dasar
│   │   ├── auth.css             # Desain editorial login & peta koordinat
│   │   └── components.css       # Modals, Toasts, Autocomplete, Status meter, Print
│   ├── js/
│   │   ├── app.js               # Pengendali modal, AJAX CSRF helper, filter tabel
│   │   └── toast.js             # Sistem notifikasi toast interaktif
│   ├── vendor/
│   │   └── chart.min.js         # Library Chart.js lokal (100% offline)
│   └── images/
│       └── logo-kartika.svg     # Logo resmi vektor SMA Kartika 1-2 Medan
├── config/
│   ├── database.php             # Konfigurasi koneksi PDO MySQL
│   └── app.php                  # Sesi, konstanta sistem, & base URL otomatis
├── includes/
│   ├── auth.php                 # Proteksi RBAC & verifikasi token CSRF
│   ├── functions.php            # Format tanggal ID, mesin sanksi, WA template & transaksi
│   ├── header.php               # Kerangka atas, breadcrumb, status & topbar
│   ├── sidebar.php              # Navigasi vertikal dinamis berdasarkan role
│   └── footer.php               # Kerangka bawah, penutup halaman & skrip
├── admin/
│   ├── dashboard.php            # Dasbor analitis administrator & Chart.js
│   ├── students.php             # CRUD data pangkalan siswa & ekspor CSV
│   ├── teachers.php             # CRUD data guru & akun portal
│   ├── classes.php              # CRUD rombongan belajar & penetapan wali kelas
│   ├── parents.php              # CRUD data wali murid
│   ├── categories.php           # CRUD kategori & bobot poin pelanggaran
│   ├── sanctions.php            # Konfigurasi ambang sanksi sekolah
│   ├── violations.php           # Audit seluruh catatan riwayat transaksi
│   ├── reports.php              # Cetak rekapitulasi KOP SURAT resmi & ekspor CSV
│   ├── users.php                # Manajemen akun portal & reset sandi
│   └── settings.php             # Konfigurasi institusi & WhatsApp Gateway
├── teacher/
│   ├── dashboard.php            # Dasbor operasional dewan guru
│   ├── add_violation.php        # FITUR UTAMA: Input pelanggaran real-time & WA
│   ├── history.php              # Riwayat input catatan guru
│   └── students.php             # Direktori pencarian siswa (read-only)
├── parent/
│   ├── dashboard.php            # Dasbor pemantauan kedisiplinan anak & meteran poin
│   ├── profile.php              # Biodata anak & panduan tata tertib sekolah
│   └── history.php              # Rincian riwayat catatan pelanggaran anak
├── api/
│   ├── students.php             # Endpoint pencarian siswa real-time
│   ├── violations.php           # Endpoint transaksi pelanggaran atomik
│   ├── notifications.php        # Endpoint transmisi notifikasi WhatsApp
│   └── dashboard.php            # Endpoint statistik sinkronisasi grafik
├── database/
│   ├── schema.sql               # Skema struktur 10 tabel relasional
│   └── seeder.sql               # Data awal realistis SMA Kartika 1-2 Medan
├── tests/
│   └── test_system.php          # Automated end-to-end test suite
├── login.php                    # Gerbang masuk sistem multi-role
├── logout.php                   # Pemusnahan sesi & pengalihan aman
├── index.php                    # Pengarah cerdas berdasarkan status login
└── README.md                    # Dokumentasi komprehensif sistem
```

---

## 7. CARA INSTALASI & MENJALANKAN DI LOCALHOST (XAMPP)

### Prasyarat:
- Komputer dengan XAMPP terpasang (Apache dan MySQL).
- PHP versi 8.0 ke atas dengan ekstensi `pdo_mysql` aktif.

### Langkah 1: Penempatan Berkas di `htdocs`
Aplikasi dapat diletakkan langsung di direktori `htdocs` XAMPP, contoh:
`C:\xampp\htdocs\school-discipline\` atau melalui direktori *junction / symlink*.

### Langkah 2: Mengimpor Basis Data MySQL
1. Pastikan modul **MySQL** pada XAMPP Control Panel telah berjalan (*Start*).
2. Buka antarmuka **PHPMyAdmin** di browser: `http://localhost/phpmyadmin/`.
3. Buat basis data baru bernama `db_kartika_disiplin`.
4. Pilih basis data tersebut, klik menu **Import**, pilih berkas:
   - `database/schema.sql` (kemudian klik *Go / Kirim*).
   - `database/seeder.sql` (kemudian klik *Go / Kirim*).
   
*Atau melalui Command Prompt / Terminal:*
```bash
mysql -u root < database/schema.sql
mysql -u root < database/seeder.sql
```

### Langkah 3: Menjalankan Aplikasi
1. Pastikan modul **Apache** pada XAMPP Control Panel telah berjalan (*Start*).
2. Buka browser dan akses alamat:
   ```
   http://localhost/school-discipline/
   ```
3. Anda akan disambut oleh Halaman Login Portal SMA Kartika 1-2 Medan.
4. Gunakan akun demo di atas untuk menguji masing-masing peran pengguna!

---

## 8. PANDUAN DEPLOYMENT ONLINE (GITHUB PAGES & CLOUD)

### Opsi A: Live Web via GitHub Pages (Static / Interactive Web Assembly)
1. Buka repositori di GitHub: `https://github.com/not-kuman/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time`
2. Masuk ke tab **Settings** &gt; **Pages**.
3. Pada bagian **Build and deployment** &gt; **Source**:
   - Pilih **GitHub Actions** (Workflow `.github/workflows/deploy-pages.yml` akan otomatis aktif mendeploy direktori `/docs`), ATAU
   - Pilih **Deploy from a branch** &gt; Branch: `main` &gt; Folder: `/docs` &gt; klik **Save**.
4. Website live dapat langsung diakses di:
   `https://not-kuman.github.io/Implementasi-Sistem-Pencatatan-Pelanggaran-dan-Poin-Kedisiplinan-Siswa-Berbasis-Web-Real-Time/`

### Opsi B: Full-Stack PHP & MySQL Cloud Deployment (Docker / Render / Railway)
Aplikasi telah dilengkapi berkas konfigurasi cloud siap pakai:
- **Docker Compose:** Jalankan `docker-compose up -d` di server Anda (port `8080`). Skema database dan seeder akan otomatis dimuat.
- **Render.com:** Hubungkan repositori GitHub Anda dan impor `render.yaml`. Render akan otomatis membuat web service PHP 8.2 dan database MySQL gratis.
- **Shared Hosting (cPanel / InfinityFree):** Unggah seluruh file ke `public_html`, buat database MySQL, impor `database/schema.sql` dan `database/seeder.sql`, lalu sesuaikan kredensial di `config/database.php`.

---

## 9. INTEGRASI PENGUJIAN OTOMATIS (TEST SUITE)

Untuk memastikan keutuhan fungsionalitas sistem (koneksi database, hashing otentikasi, transaksi atomik pembaruan poin, mesin sanksi, dan format notifikasi WhatsApp), jalankan test suite otomatis melalui terminal:

```bash
php tests/test_system.php
```

Hasil eksekusi akan memvalidasi seluruh sub-sistem dan menjamin tidak ada regresi logika pada modul aplikasi.

---

## 10. IDENTITAS TIM PENELITI & HAK CIPTA

Aplikasi dan sistem ini dikembangkan sebagai implementasi digital mandiri (original built-from-scratch) berdasarkan penelitian:
**"Implementasi Sistem Pencatatan Pelanggaran dan Poin Kedisiplinan Siswa Berbasis Web Real-Time"**

**© 2026 — Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.**

