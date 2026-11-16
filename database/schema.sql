-- ==============================================================
-- SISTEM INFORMASI PENCATATAN PELANGGARAN DAN POIN KEDISIPLINAN SISWA
-- PENCATATAN PELANGGARAN DAN POIN KEDISIPLINAN
-- DATABASE SCHEMA
-- ==============================================================

CREATE DATABASE IF NOT EXISTS `db_kartika_disiplin` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `db_kartika_disiplin`;

-- Matikan foreign key checks saat build tabel
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABEL USERS (Akun Otentikasi Multi-Role)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'teacher', 'parent') NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL TEACHERS (Data Pendidik & Guru BK)
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `nip` VARCHAR(30) NOT NULL UNIQUE,
    `nama_guru` VARCHAR(100) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `jabatan` VARCHAR(100) NOT NULL DEFAULT 'Guru Bimbingan Konseling',
    `no_telp` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL CLASSES (Data Kelas & Rombongan Belajar)
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kelas` VARCHAR(50) NOT NULL UNIQUE,
    `tingkat` ENUM('X', 'XI', 'XII') NOT NULL,
    `jurusan` VARCHAR(50) NOT NULL DEFAULT 'MIPA',
    `wali_kelas_id` INT NULL,
    `tahun_ajaran` VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_classes_wali` FOREIGN KEY (`wali_kelas_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL STUDENTS (Data Siswa & Akumulasi Poin)
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nis` VARCHAR(30) NOT NULL UNIQUE,
    `nisn` VARCHAR(30) NULL UNIQUE,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `jenis_kelamin` ENUM('L', 'P') NOT NULL,
    `class_id` INT NOT NULL,
    `tempat_lahir` VARCHAR(50) NULL,
    `tanggal_lahir` DATE NULL,
    `alamat` TEXT NULL,
    `no_telp_wali` VARCHAR(20) NOT NULL,
    `total_poin` INT NOT NULL DEFAULT 0,
    `status_siswa` ENUM('aktif', 'lulus', 'pindah', 'dikeluarkan') NOT NULL DEFAULT 'aktif',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_students_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL PARENTS (Data Orang Tua / Wali Siswa)
DROP TABLE IF EXISTS `parents`;
CREATE TABLE `parents` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `student_id` INT NOT NULL,
    `nama_wali` VARCHAR(100) NOT NULL,
    `hubungan` ENUM('Ayah', 'Ibu', 'Wali') NOT NULL DEFAULT 'Ayah',
    `pekerjaan` VARCHAR(100) NULL,
    `no_telp` VARCHAR(20) NOT NULL,
    `alamat` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_parents_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_parents_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL VIOLATION_CATEGORIES (Master Kategori Pelanggaran & Bobot Poin)
DROP TABLE IF EXISTS `violation_categories`;
CREATE TABLE `violation_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode` VARCHAR(20) NOT NULL UNIQUE,
    `nama_pelanggaran` VARCHAR(150) NOT NULL,
    `tingkat` ENUM('Ringan', 'Sedang', 'Berat') NOT NULL,
    `poin` INT NOT NULL,
    `deskripsi` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL SANCTIONS (Ketentuan Tingkat Sanksi / Ambang Batas Poin)
DROP TABLE IF EXISTS `sanctions`;
CREATE TABLE `sanctions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_sanksi` VARCHAR(150) NOT NULL,
    `poin_min` INT NOT NULL,
    `poin_max` INT NOT NULL,
    `tindakan` TEXT NOT NULL,
    `tingkat_urgensi` ENUM('Rendah', 'Sedang', 'Tinggi', 'Kritis') NOT NULL DEFAULT 'Sedang',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABEL VIOLATION_RECORDS (Riwayat Pencatatan Transaksi Pelanggaran)
DROP TABLE IF EXISTS `violation_records`;
CREATE TABLE `violation_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_transaksi` VARCHAR(50) NOT NULL UNIQUE,
    `student_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `teacher_id` INT NOT NULL,
    `poin` INT NOT NULL,
    `tanggal_kejadian` DATE NOT NULL,
    `waktu_kejadian` TIME NOT NULL,
    `tempat_kejadian` VARCHAR(100) NOT NULL DEFAULT 'Lingkungan Sekolah',
    `keterangan` TEXT NOT NULL,
    `status_tindak_lanjut` ENUM('Menunggu', 'Diproses', 'Selesai') NOT NULL DEFAULT 'Diproses',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_violations_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_violations_category` FOREIGN KEY (`category_id`) REFERENCES `violation_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_violations_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABEL NOTIFICATIONS (Log Notifikasi WhatsApp Gateway)
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `violation_record_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `parent_phone` VARCHAR(20) NOT NULL,
    `message` TEXT NOT NULL,
    `provider` VARCHAR(50) NOT NULL DEFAULT 'WAGateway-Simulator',
    `status` ENUM('pending', 'sent', 'failed', 'simulated') NOT NULL DEFAULT 'simulated',
    `response_log` TEXT NULL,
    `sent_by_user_id` INT NULL,
    `sent_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_notifications_violation` FOREIGN KEY (`violation_record_id`) REFERENCES `violation_records` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notifications_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`sent_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABEL SYSTEM_SETTINGS (Konfigurasi Global & Integrasi WhatsApp)
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(50) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Hidupkan kembali foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
