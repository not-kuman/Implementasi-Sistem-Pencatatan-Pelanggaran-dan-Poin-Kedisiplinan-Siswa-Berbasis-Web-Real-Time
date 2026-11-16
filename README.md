# Sistem Informasi Kedisiplinan Siswa

## Deskripsi Website

Sistem Informasi Kedisiplinan Siswa merupakan aplikasi berbasis web yang dikembangkan untuk membantu institusi pendidikan dalam mengelola pencatatan pelanggaran tata tertib peserta didik, klasifikasi kategori dan bobot poin pelanggaran, perhitungan akumulasi poin kedisiplinan secara otomatis, penentuan tingkat sanksi pembinaan, serta pemantauan riwayat pelanggaran secara transparan dan akuntabel.

Aplikasi ini memiliki tiga hak akses pengguna (*Role-Based Access Control*):
- **Admin**: Mengelola seluruh data master (siswa, guru, kelas, wali siswa, kategori pelanggaran, tingkat sanksi), melihat riwayat audit pelanggaran, mengunduh laporan rekapitulasi, dan memantau dasbor analitis.
- **Guru**: Mencari data siswa, mencatat insiden pelanggaran baru secara real-time dengan transaksi atomik, melihat bobot poin serta proyeksi sanksi, dan memantau riwayat pencatatan yang dilakukan.
- **Wali Siswa**: Memantau rekam jejak kedisiplinan anak secara *read-only*, melihat total akumulasi poin denda, status pembinaan/sanksi aktif, dan riwayat detail kejadian pelanggaran.

---

## Cara Menjalankan Website di Localhost

Aplikasi ini dibangun menggunakan PHP Native dan MySQL tanpa memerlukan dependensi Composer maupun instalasi Node.js/npm. Seluruh pustaka pendukung (seperti Chart.js) telah tersedia secara lokal di dalam proyek.

Ikuti langkah-langkah berikut untuk menjalankan website di komputer lokal menggunakan **XAMPP**:

1. **Pastikan XAMPP Terpasang**  
   Pastikan Anda telah menginstal XAMPP dengan versi PHP 8.0 atau yang lebih baru di komputer Anda.

2. **Jalankan Layanan Apache dan MySQL**  
   Buka aplikasi **XAMPP Control Panel**, lalu klik tombol **Start** pada modul **Apache** dan **MySQL** hingga indikator berwarna hijau.

3. **Salin Folder Proyek ke direktori `htdocs`**  
   Salin atau pindahkan folder proyek ini ke dalam direktori `htdocs` instalasi XAMPP Anda.  
   - Contoh jalur Windows: `C:\xampp\htdocs\school-discipline`

4. **Buka phpMyAdmin**  
   Buka peramban (*web browser*) Anda, lalu akses alamat:  
   `http://localhost/phpmyadmin`

5. **Buat Database Baru**  
   Buat basis data baru dengan nama:  
   `db_kartika_disiplin`  
   Gunakan kolasi default (*utf8mb4_unicode_ci* atau *utf8mb4_general_ci*).

6. **Import File SQL Proyek**  
   Pada menu phpMyAdmin database `db_kartika_disiplin`, pilih tab **Import**, lalu impor file SQL secara berurutan:
   - Pertama, pilih dan import file: `database/schema.sql` (untuk membuat struktur 10 tabel sistem).
   - Kedua, pilih dan import file: `database/seeder.sql` (untuk mengisi master data awal, akun pengguna demo, serta 35 catatan pelanggaran terverifikasi).
   
   *(Alternatif melalui terminal: jalankan `php database/seed.php` untuk mengisi ulang data demonstrasi secara instan).*

7. **Periksa Konfigurasi Koneksi Database**  
   Buka file konfigurasi basis data pada:  
   `config/database.php`  
   Pastikan parameter koneksi sesuai dengan pengaturan lokal XAMPP Anda (secara default XAMPP menggunakan `DB_HOST = 127.0.0.1`, `DB_USER = root`, dan `DB_PASS = ""` tanpa kata sandi).

8. **Akses Website Melalui Browser**  
   Buka browser Anda dan kunjungi URL sesuai dengan nama folder di `htdocs`, misalnya:  
   `http://localhost/school-discipline/login.php`

9. **Masuk Menggunakan Akun Demonstrasi**  
   Gunakan salah satu kredensial akun demonstrasi di bawah ini untuk menguji alur kerja Admin, Guru, maupun Wali Siswa.

---

## Akun Login Demonstrasi

Berikut adalah akun pengguna yang terdaftar di basis data untuk kebutuhan demonstrasi pengujian lokal:

| Nama Anggota | Username | Password Demonstrasi | Role | Hak Akses Utama |
| :--- | :--- | :--- | :--- | :--- |
| **ARSYAD SADEWA** | `arsyad` | `arsyad123` | Admin | Kelola data master, konfigurasi sanksi, rekap laporan, dasbor statistik |
| **BAGAS PRASETYO** | `bagas` | `guru123` | Guru | Input pelanggaran siswa, kalkulasi poin, pencarian siswa, riwayat guru |
| **NIKEN AYU RESDIANA** | `niken` | `wali123` | Wali Siswa | Pantau poin anak (M. Farhan Pratama), status sanksi, riwayat pelanggaran (*read-only*) |

> **Catatan Kredensial:**  
> Kredensial login di atas disimpan menggunakan algoritma *password hashing* BCRYPT yang aman dan semata-mata ditujukan untuk keperluan demonstrasi lokal. Hanya tiga akun resmi kelompok di atas yang terdaftar aktif dalam basis data sistem.

---

## Hak Cipta & Orisinalitas

© 2026 — Copyright by ARSYAD SADEWA, BAGAS PRASETYO, NIKEN AYU RESDIANA. All Rights Reserved.
