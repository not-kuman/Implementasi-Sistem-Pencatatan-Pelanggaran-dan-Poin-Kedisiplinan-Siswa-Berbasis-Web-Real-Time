-- ==============================================================
-- SISTEM INFORMASI PENCATATAN PELANGGARAN DAN POIN KEDISIPLINAN SISWA
-- SMA KARTIKA 1-2 MEDAN
-- DATABASE SEEDER (DUMMY REALISTIS & AKUN DEMO)
-- ==============================================================

USE `db_kartika_disiplin`;

SET FOREIGN_KEY_CHECKS = 0;

-- Kosongkan data lama
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `violation_records`;
TRUNCATE TABLE `sanctions`;
TRUNCATE TABLE `violation_categories`;
TRUNCATE TABLE `parents`;
TRUNCATE TABLE `students`;
TRUNCATE TABLE `classes`;
TRUNCATE TABLE `teachers`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `system_settings`;

-- 1. SEED USERS
-- Password Default:
-- admin: admin123 ($2y$10$2GuwKegfU24eoAF2QKSDb.E.Bpnj038qie7PjU8GwWPMBOg9nRaIC)
-- guru: guru123 ($2y$10$laRwgdCRrI60nJkwok47eOq1mj8iGJ343ZswzYFG4/hCfLzLocrPC)
-- wali: wali123 ($2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$2GuwKegfU24eoAF2QKSDb.E.Bpnj038qie7PjU8GwWPMBOg9nRaIC', 'Administrator Disiplin', 'admin', 'active'),
(2, 'guru1', '$2y$10$laRwgdCRrI60nJkwok47eOq1mj8iGJ343ZswzYFG4/hCfLzLocrPC', 'Drs. Hendra Gunawan, M.Pd', 'teacher', 'active'),
(3, 'guru2', '$2y$10$laRwgdCRrI60nJkwok47eOq1mj8iGJ343ZswzYFG4/hCfLzLocrPC', 'Siti Aminah, S.Pd', 'teacher', 'active'),
(4, 'guru3', '$2y$10$laRwgdCRrI60nJkwok47eOq1mj8iGJ343ZswzYFG4/hCfLzLocrPC', 'Rizky Pratama, S.Pd', 'teacher', 'active'),
(5, 'wali_farhan', '$2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy', 'Bpk. Joko Susilo', 'parent', 'active'),
(6, 'wali_amanda', '$2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy', 'Ibu Ratna Dewi', 'parent', 'active'),
(7, 'wali_dimas', '$2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy', 'Bpk. Agus Setiawan', 'parent', 'active'),
(8, 'wali_keisha', '$2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy', 'Ibu Marlina Siregar', 'parent', 'active'),
(9, 'wali_bagus', '$2y$10$2h3qEc70xO7xHebpx7R9WObLx6ZOx18Dl8DSe2Zou0L.xrRGaHpjy', 'Bpk. Budi Santoso', 'parent', 'active');

-- 2. SEED TEACHERS
INSERT INTO `teachers` (`id`, `user_id`, `nip`, `nama_guru`, `jenis_kelamin`, `jabatan`, `no_telp`, `email`) VALUES
(1, 2, '198005122005011002', 'Drs. Hendra Gunawan, M.Pd', 'L', 'Koordinator Bimbingan Konseling', '081260112233', 'hendra.gunawan@kartika12.sch.id'),
(2, 3, '198503142008012003', 'Siti Aminah, S.Pd', 'P', 'Guru Bimbingan Konseling', '081375223344', 'siti.aminah@kartika12.sch.id'),
(3, 4, '199008222014021001', 'Rizky Pratama, S.Pd', 'L', 'Staf Kesiswaan & Kedisiplinan', '082168334455', 'rizky.pratama@kartika12.sch.id'),
(4, NULL, '198811102010012005', 'Dewi Lestari, M.Si', 'P', 'Guru Matematika (Wali Kelas)', '085270445566', 'dewi.lestari@kartika12.sch.id');

-- 3. SEED CLASSES
INSERT INTO `classes` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `wali_kelas_id`, `tahun_ajaran`) VALUES
(1, 'X MIPA 1', 'X', 'MIPA', 4, '2026/2027'),
(2, 'X MIPA 2', 'X', 'MIPA', 2, '2026/2027'),
(3, 'X IPS 1', 'X', 'IPS', 3, '2026/2027'),
(4, 'XI MIPA 1', 'XI', 'MIPA', 1, '2026/2027'),
(5, 'XI IPS 1', 'XI', 'IPS', 2, '2026/2027'),
(6, 'XII MIPA 1', 'XII', 'MIPA', 4, '2026/2027'),
(7, 'XII IPS 1', 'XII', 'IPS', 3, '2026/2027');

-- 4. SEED STUDENTS
INSERT INTO `students` (`id`, `nis`, `nisn`, `nama_lengkap`, `jenis_kelamin`, `class_id`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_telp_wali`, `total_poin`, `status_siswa`) VALUES
(1, '20261001', '0089123401', 'M. Farhan Pratama', 'L', 1, 'Medan', '2008-04-12', 'Jl. Brigjend Katamso Gg. Melati No. 14, Medan', '081298760001', 25, 'aktif'),
(2, '20261002', '0089123402', 'Amanda Putri Utami', 'P', 1, 'Medan', '2008-07-23', 'Jl. Sakti Lubis No. 88, Medan Maimun', '081298760002', 5, 'aktif'),
(3, '20261003', '0078123403', 'Dimas Arya Nugraha', 'L', 5, 'Binjai', '2007-03-15', 'Jl. STM Gg. Suka Maju No. 5, Medan', '081298760003', 45, 'aktif'),
(4, '20261004', '0078123404', 'Keisha Anindya', 'P', 4, 'Medan', '2007-11-09', 'Jl. Juanda No. 120, Medan', '081298760004', 0, 'aktif'),
(5, '20261005', '0067123405', 'Bagus Wicaksono', 'L', 7, 'Deli Serdang', '2006-05-18', 'Jl. Halat No. 45, Medan', '081298760005', 60, 'aktif'),
(6, '20261006', '0089123406', 'Nabila Zahra', 'P', 2, 'Medan', '2008-09-30', 'Jl. Alfalah No. 27, Medan', '081298760006', 10, 'aktif'),
(7, '20261007', '0089123407', 'Aditya Pratama', 'L', 3, 'Tebing Tinggi', '2008-01-14', 'Jl. Garu I No. 34, Medan', '081298760007', 35, 'aktif'),
(8, '20261008', '0078123408', 'Rian Hidayat', 'L', 5, 'Medan', '2007-08-20', 'Jl. Pelajar Ujung No. 19, Medan', '081298760008', 15, 'aktif'),
(9, '20261009', '0067123409', 'Dinda Kirana', 'P', 6, 'Medan', '2006-12-05', 'Jl. Sisingamangaraja No. 67, Medan', '081298760009', 0, 'aktif'),
(10, '20261010', '0067123410', 'Fajar Ramadhan', 'L', 7, 'Pematangsiantar', '2006-02-28', 'Jl. Bromo Gg. Santun No. 8, Medan', '081298760010', 80, 'aktif');

-- 5. SEED PARENTS
INSERT INTO `parents` (`id`, `user_id`, `student_id`, `nama_wali`, `hubungan`, `pekerjaan`, `no_telp`, `alamat`) VALUES
(1, 5, 1, 'Bpk. Joko Susilo', 'Ayah', 'Wiraswasta', '081298760001', 'Jl. Brigjend Katamso Gg. Melati No. 14, Medan'),
(2, 6, 2, 'Ibu Ratna Dewi', 'Ibu', 'Pegawai Swasta', '081298760002', 'Jl. Sakti Lubis No. 88, Medan Maimun'),
(3, 7, 3, 'Bpk. Agus Setiawan', 'Ayah', 'PNS', '081298760003', 'Jl. STM Gg. Suka Maju No. 5, Medan'),
(4, 8, 4, 'Ibu Marlina Siregar', 'Ibu', 'Dosen', '081298760004', 'Jl. Juanda No. 120, Medan'),
(5, 9, 5, 'Bpk. Budi Santoso', 'Ayah', 'Wiraswasta', '081298760005', 'Jl. Halat No. 45, Medan');

-- 6. SEED VIOLATION_CATEGORIES
INSERT INTO `violation_categories` (`id`, `kode`, `nama_pelanggaran`, `tingkat`, `poin`, `deskripsi`, `status`) VALUES
-- Ringan
(1, 'R-01', 'Datang terlambat ke sekolah (< 15 menit)', 'Ringan', 5, 'Siswa hadir melewati bel masuk pukul 07.15 WIB tanpa alasan darurat sah.', 'active'),
(2, 'R-02', 'Tidak mengenakan atribut seragam lengkap', 'Ringan', 5, 'Tidak memakai dasi, ikat pinggang standar, atau kaos kaki berlogo sekolah.', 'active'),
(3, 'R-03', 'Rambut tidak rapi / melebihi ketentuan putra', 'Ringan', 5, 'Panjang rambut melewati kerah kemeja atau telinga bagi siswa putra.', 'active'),
(4, 'R-04', 'Membuang sampah sembarangan di area sekolah', 'Ringan', 5, 'Membuang sampah plastik/kertas tidak pada tempat pemilahan sampah.', 'active'),
(5, 'R-05', 'Tidak memakai sepatu hitam polos bertali', 'Ringan', 5, 'Menggunakan sepatu warna-warni atau sandal di lingkungan pembelajaran.', 'active'),
(6, 'R-06', 'Makan / minum saat pembelajaran di kelas', 'Ringan', 10, 'Makan makanan berat saat guru sedang mengajar tanpa izin khusus.', 'active'),
(7, 'R-07', 'Terlambat masuk kelas setelah istirahat', 'Ringan', 10, 'Siswa belum berada di kelas lebih dari 10 menit setelah bel istirahat usai.', 'active'),
(8, 'R-08', 'Memakai kosmetik / perhiasan berlebihan', 'Ringan', 10, 'Penggunaan makeup tebal, lipstik mencolok, atau perhiasan emas berlebih.', 'active'),

-- Sedang
(9, 'S-01', 'Meninggalkan kelas / bolos jam pelajaran', 'Sedang', 15, 'Tidak berada di kelas saat KBM berlangsung tanpa surat dispensasi/izin.', 'active'),
(10, 'S-02', 'Bermain game / ponsel tanpa izin saat KBM', 'Sedang', 15, 'Mengoperasikan game online di handphone saat instruksi pelajaran.', 'active'),
(11, 'S-03', 'Melompati pagar sekolah / keluar area tanpa izin', 'Sedang', 20, 'Keluar area pagar sekolah sebelum jam kepulangan resmi berakhir.', 'active'),
(12, 'S-04', 'Mencontek / berbuat curang saat ulangan', 'Sedang', 20, 'Membawa contekan fisik atau bertukar jawaban digital saat asesmen.', 'active'),
(13, 'S-05', 'Merusak fasilitas sekolah / mencoret meja kursi', 'Sedang', 20, 'Vandalisme terhadap sarana prasarana meja belajar atau dinding.', 'active'),
(14, 'S-06', 'Merokok / membawa rokok atau vape di sekolah', 'Sedang', 25, 'Menghisap atau menyimpan rokok kretek/elektrik di tas/saku.', 'active'),
(15, 'S-07', 'Berperilaku tidak sopan terhadap guru/staf', 'Sedang', 25, 'Mengeluarkan kata kasar atau gestur menantang instruksi tenaga pendidik.', 'active'),
(16, 'S-08', 'Membawa motor knalpot bising / tidak ber-STNK', 'Sedang', 20, 'Membawa kendaraan bersuara memekakkan telinga ke area parkir sekolah.', 'active'),

-- Berat
(17, 'B-01', 'Terlibat perkelahian / tawuran antarpelajar', 'Berat', 50, 'Aksi kekerasan fisik baik satu lawan satu maupun berkelompok.', 'active'),
(18, 'B-02', 'Tindakan perundungan (bullying) fisik/verbal', 'Berat', 50, 'Intimidasi, penghinaan sistematis, atau kekerasan psikis kepada sesama siswa.', 'active'),
(19, 'B-03', 'Pencurian uang atau barang milik orang lain', 'Berat', 50, 'Mengambil barang milik sekolah, guru, atau teman tanpa hak.', 'active'),
(20, 'B-04', 'Membawa senjata tajam atau benda berbahaya', 'Berat', 75, 'Membawa pisau, gear, gir motor, atau benda penusuk tanpa izin KBM seni.', 'active'),
(21, 'B-05', 'Membawa / mengonsumsi miras atau narkoba', 'Berat', 100, 'Zat adiktif terlarang, obat psikotropika tanpa resep dokter di sekolah.', 'active'),
(22, 'B-06', 'Tindakan asusila atau pelecehan seksual', 'Berat', 100, 'Perbuatan tidak senonoh baik fisik maupun penyebaran konten tidak pantas.', 'active'),
(23, 'B-07', 'Pemalakan atau pemerasan uang terhadap teman', 'Berat', 40, 'Memaksa siswa lain menyerahkan uang saku disertai ancaman.', 'active'),
(24, 'B-08', 'Mencemarkan nama baik sekolah di media sosial', 'Berat', 40, 'Membuat konten ujaran kebencian atau fitnah yang merugikan martabat sekolah.', 'active');

-- 7. SEED SANCTIONS
INSERT INTO `sanctions` (`id`, `nama_sanksi`, `poin_min`, `poin_max`, `tindakan`, `tingkat_urgensi`) VALUES
(1, 'Peringatan Lisan & Bimbingan Wali Kelas', 1, 15, 'Teguran langsung dari guru piket dan konseling singkat bersama wali kelas untuk evaluasi komitmen kedisiplinan.', 'Rendah'),
(2, 'Surat Peringatan I (SP 1) & Penugasan Edukatif', 16, 30, 'Penerbitan SP 1 resmi, penugasan literasi kedisiplinan atau kebersihan lingkungan sekolah selama 3 hari di luar jam KBM.', 'Sedang'),
(3, 'Surat Peringatan II (SP 2) & Pemanggilan Orang Tua I', 31, 50, 'Penerbitan SP 2, orang tua/wali wajib hadir ke ruang BK untuk menandatangani pakta integritas pembinaan.', 'Tinggi'),
(4, 'Surat Peringatan III (SP 3) & Skorsing 3 Hari', 51, 75, 'Konferensi kasus melibatkan Kepala Sekolah, Tim BK, Wali Kelas, dan Wali Siswa. Siswa diskorsing selama 3 hari pembelajaran.', 'Kritis'),
(5, 'Skorsing Terakhir 7 Hari & Perjanjian Bermaterai', 76, 99, 'Skorsing akademis 7 hari kerja. Penandatanganan surat perjanjian bermaterai Rp 10.000 sebagai kesempatan terakhir di sekolah.', 'Kritis'),
(6, 'Pengembalian Siswa Kepada Orang Tua (Drop Out)', 100, 999, 'Sidang pleno dewan guru memutuskan pemberhentian status peserta didik dan hak pendidikannya dikembalikan sepenuhnya kepada keluarga.', 'Kritis');

-- 8. SEED VIOLATION_RECORDS
-- Farhan (ID 1, total poin 25): Datang terlambat (5), Tidak atribut (5), Bolos jam pelajaran (15)
-- Amanda (ID 2, total poin 5): Datang terlambat (5)
-- Dimas (ID 3, total poin 45): Merokok di sekolah (25), Bolos (15), Terlambat (5)
-- Bagus (ID 5, total poin 60): Tawuran/perkelahian (50), Terlambat masuk kelas (10)
-- Nabila (ID 6, total poin 10): Makan di kelas (10)
-- Aditya (ID 7, total poin 35): Main game saat KBM (15), Mencontek ujian (20)
-- Rian (ID 8, total poin 15): Bolos pelajaran (15)
-- Fajar (ID 10, total poin 80): Senjata tajam (75), Terlambat (5)
INSERT INTO `violation_records` (`id`, `kode_transaksi`, `student_id`, `category_id`, `teacher_id`, `poin`, `tanggal_kejadian`, `waktu_kejadian`, `tempat_kejadian`, `keterangan`, `status_tindak_lanjut`) VALUES
(1, 'TRX-202610-001', 1, 1, 1, 5, '2026-10-15', '07:25:00', 'Gerbang Utama', 'Siswa hadir terlambat 15 menit dengan alasan rantai sepeda motor lepas.', 'Selesai'),
(2, 'TRX-202610-002', 1, 2, 2, 5, '2026-10-22', '07:30:00', 'Lapangan Upacara', 'Tidak mengenakan dasi dan ikat pinggang berlogo sekolah pada hari Senin.', 'Selesai'),
(3, 'TRX-202610-003', 1, 9, 3, 15, '2026-11-01', '10:15:00', 'Kantin Belakang', 'Ditemukan nongkrong di area kantin saat jam pelajaran Fisika berlangsung.', 'Diproses'),
(4, 'TRX-202610-004', 2, 1, 1, 5, '2026-10-28', '07:20:00', 'Gerbang Utama', 'Terlambat masuk sekolah karena hujan deras di perjalanan.', 'Selesai'),
(5, 'TRX-202610-005', 3, 14, 3, 25, '2026-10-18', '11:45:00', 'Toilet Belakang Lab', 'Tertangkap basah membawa dan menghisap vape bersama rekan di toilet sekolah.', 'Diproses'),
(6, 'TRX-202610-006', 3, 9, 2, 15, '2026-10-25', '09:00:00', 'Parkiran Belakang', 'Meninggalkan jam pelajaran Sosiologi tanpa surat izin dari guru piket.', 'Diproses'),
(7, 'TRX-202610-007', 3, 1, 1, 5, '2026-11-02', '07:22:00', 'Gerbang Utama', 'Terlambat 10 menit.', 'Diproses'),
(8, 'TRX-202610-008', 5, 17, 1, 50, '2026-10-12', '14:30:00', 'Luar Gerbang Samping', 'Terlibat insiden perkelahian dengan siswa sekolah sebelah sepulang KBM.', 'Diproses'),
(9, 'TRX-202610-009', 5, 7, 2, 10, '2026-10-29', '13:15:00', 'Ruang Kelas XII IPS 1', 'Terlambat masuk kelas selama 15 menit sesudah jam istirahat sholat Dzuhur.', 'Selesai'),
(10, 'TRX-202610-010', 6, 6, 2, 10, '2026-10-20', '08:45:00', 'Ruang Kelas X MIPA 2', 'Makan makanan ringan di dalam kelas saat pemaparan materi Biologi.', 'Selesai'),
(11, 'TRX-202610-011', 7, 10, 3, 15, '2026-10-24', '10:30:00', 'Ruang Kelas X IPS 1', 'Bermain game online di smartphone saat guru sedang memberikan tugas.', 'Selesai'),
(12, 'TRX-202610-012', 7, 12, 1, 20, '2026-11-02', '08:15:00', 'Ruang Kelas X IPS 1', 'Membawa lembaran contekan terselip di bawah meja saat Ulangan Harian Sejarah.', 'Diproses'),
(13, 'TRX-202610-013', 8, 9, 3, 15, '2026-10-30', '11:00:00', 'Gedung Olahraga', 'Membolos dari jam pelajaran Geografi.', 'Selesai'),
(14, 'TRX-202610-014', 10, 20, 1, 75, '2026-10-08', '07:10:00', 'Pemeriksaan Rutin Pintu Masuk', 'Membawa pisau lipat bergerigi di dalam ransel sekolah.', 'Diproses'),
(15, 'TRX-202610-015', 10, 1, 2, 5, '2026-10-27', '07:28:00', 'Gerbang Utama', 'Terlambat hadir.', 'Diproses');

-- 9. SEED NOTIFICATIONS
INSERT INTO `notifications` (`id`, `violation_record_id`, `student_id`, `parent_phone`, `message`, `provider`, `status`, `response_log`, `sent_by_user_id`, `sent_at`) VALUES
(1, 1, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Datang terlambat ke sekolah (< 15 menit)\nTanggal: 2026-10-15\nPoin Pelanggaran: 5\nTotal Akumulasi Poin: 5\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSMA Kartika 1-2', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001","message_id":"SIM-MSG-88491"}', 2, '2026-10-15 07:45:10'),
(2, 2, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Tidak mengenakan atribut seragam lengkap\nTanggal: 2026-10-22\nPoin Pelanggaran: 5\nTotal Akumulasi Poin: 10\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSMA Kartika 1-2', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001","message_id":"SIM-MSG-88492"}', 3, '2026-10-22 08:00:20'),
(3, 3, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Meninggalkan kelas / bolos jam pelajaran\nTanggal: 2026-11-01\nPoin Pelanggaran: 15\nTotal Akumulasi Poin: 25\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSMA Kartika 1-2', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001","message_id":"SIM-MSG-88493"}', 4, '2026-11-01 10:35:12'),
(4, 5, 3, '081298760003', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Dimas Arya Nugraha dari kelas XI IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Merokok / membawa rokok atau vape di sekolah\nTanggal: 2026-10-18\nPoin Pelanggaran: 25\nTotal Akumulasi Poin: 25\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSMA Kartika 1-2', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760003","message_id":"SIM-MSG-88494"}', 4, '2026-10-18 12:10:45'),
(5, 8, 5, '081298760005', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Bagus Wicaksono dari kelas XII IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Terlibat perkelahian / tawuran antarpelajar\nTanggal: 2026-10-12\nPoin Pelanggaran: 50\nTotal Akumulasi Poin: 50\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSMA Kartika 1-2', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760005","message_id":"SIM-MSG-88495"}', 2, '2026-10-12 15:05:00');

-- 10. SEED SYSTEM_SETTINGS
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('school_name', 'SMA Kartika 1-2 Medan', 'Nama resmi institusi sekolah'),
('school_address', 'Jl. Brigjend Katamso No. 235, Sei Mati, Kec. Medan Maimun, Kota Medan, Sumatera Utara 20158', 'Alamat lengkap lokasi sekolah'),
('school_phone', '(061) 7864321', 'Nomor telepon resmi sekolah'),
('school_email', 'info@kartika12medan.sch.id', 'Email resmi korespondensi sekolah'),
('school_lat', '3.565800', 'Koordinat lintang sekolah (peta lokasi)'),
('school_lng', '98.687200', 'Koordinat bujur sekolah (peta lokasi)'),
('academic_year', '2026/2027', 'Tahun ajaran operasional aktif'),
('wa_gateway_provider', 'WAGateway-Simulator', 'Nama penyedia gateway WhatsApp (Fonnte / Wablas / Ruanggwa / Simulator)'),
('wa_gateway_api_key', 'DEMO_KARTIKA_API_KEY_2026', 'API Token kredensial gateway WhatsApp'),
('wa_gateway_sender', '081260112233', 'Nomor WhatsApp resmi pengirim notifikasi'),
('wa_demo_mode', '1', '1: Mode Demo Simulator Aktif, 0: Mode Produksi Live API Gateway');

SET FOREIGN_KEY_CHECKS = 1;
