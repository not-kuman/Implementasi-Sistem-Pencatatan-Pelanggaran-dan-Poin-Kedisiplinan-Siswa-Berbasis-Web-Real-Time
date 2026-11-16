-- ==============================================================
-- SISTEM INFORMASI PENCATATAN PELANGGARAN DAN POIN KEDISIPLINAN SISWA
-- DATABASE SEEDER (DUMMY REALISTIS & AKUN DEMO RESMI KELOMPOK)
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

-- 1. SEED USERS (Tiga Akun Utama Anggota Kelompok Sesuai Ketentuan Tugas)
-- Kredensial Login:
-- arsyad: arsyad123 (Administrator)
-- bagas:  guru123   (Guru / BK)
-- niken:  wali123   (Wali Siswa)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `status`) VALUES
(1, 'arsyad', '$2y$10$SiwEirBUCC36cYgZHC1GJee9wbW4wbxCXkHzdtclhcNW9UBd87rMa', 'ARSYAD SADEWA', 'admin', 'active'),
(2, 'bagas', '$2y$10$qdse/2df4ufpMO7qPqnk0.FQOIAXFrtQBkKyjccaOVT7RBcmboCgO', 'BAGAS PRASETYO', 'teacher', 'active'),
(3, 'niken', '$2y$10$JiJJT4636fRzMZx3qel2ce/2IrpWW7C0hKi0h.kVYcEudnzx8WyZ6', 'NIKEN AYU RESDIANA', 'parent', 'active');

-- 2. SEED TEACHERS
INSERT INTO `teachers` (`id`, `user_id`, `nip`, `nama_guru`, `jenis_kelamin`, `jabatan`, `no_telp`, `email`) VALUES
(1, 2, '198804152012011003', 'BAGAS PRASETYO', 'L', 'Koordinator Bimbingan Konseling & Kedisiplinan', '081260112233', 'bagas.prasetyo@kedisiplinan.sch.id'),
(2, NULL, '198503142008012003', 'Siti Aminah, S.Pd', 'P', 'Guru Bimbingan Konseling', '081375223344', 'siti.aminah@kedisiplinan.sch.id'),
(3, NULL, '199008222014021001', 'Rizky Pratama, S.Pd', 'L', 'Staf Kesiswaan & Kedisiplinan', '082168334455', 'rizky.pratama@kedisiplinan.sch.id'),
(4, NULL, '198811102010012005', 'Dewi Lestari, M.Si', 'P', 'Guru Matematika (Wali Kelas)', '085270445566', 'dewi.lestari@kedisiplinan.sch.id'),
(5, NULL, '198005122005011002', 'Drs. Hendra Gunawan, M.Pd', 'L', 'Guru Senior Pembina Karakter', '081260889900', 'hendra.gunawan@kedisiplinan.sch.id');

-- 3. SEED CLASSES
INSERT INTO `classes` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `wali_kelas_id`, `tahun_ajaran`) VALUES
(1, 'X MIPA 1', 'X', 'MIPA', 4, '2026/2027'),
(2, 'X MIPA 2', 'X', 'MIPA', 2, '2026/2027'),
(3, 'X IPS 1', 'X', 'IPS', 3, '2026/2027'),
(4, 'XI MIPA 1', 'XI', 'MIPA', 1, '2026/2027'),
(5, 'XI IPS 1', 'XI', 'IPS', 2, '2026/2027'),
(6, 'XII MIPA 1', 'XII', 'MIPA', 4, '2026/2027'),
(7, 'XII IPS 1', 'XII', 'IPS', 3, '2026/2027');

-- 4. SEED STUDENTS (Poin Kumulatif Terverifikasi)
INSERT INTO `students` (`id`, `nis`, `nisn`, `nama_lengkap`, `jenis_kelamin`, `class_id`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_telp_wali`, `total_poin`, `status_siswa`) VALUES
(1, '20261001', '0089123401', 'M. Farhan Pratama', 'L', 1, 'Jakarta', '2008-04-12', 'Jl. Melati No. 14', '081298760001', 40, 'aktif'),
(2, '20261002', '0089123402', 'Amanda Putri Utami', 'P', 1, 'Bandung', '2008-07-23', 'Jl. Sakti No. 88', '081298760002', 20, 'aktif'),
(3, '20261003', '0078123403', 'Dimas Arya Nugraha', 'L', 5, 'Surabaya', '2007-03-15', 'Jl. STM No. 5', '081298760003', 60, 'aktif'),
(4, '20261004', '0078123404', 'Keisha Anindya', 'P', 4, 'Semarang', '2007-11-09', 'Jl. Juanda No. 120', '081298760004', 5, 'aktif'),
(5, '20261005', '0067123405', 'Bagus Wicaksono', 'L', 7, 'Yogyakarta', '2006-05-18', 'Jl. Halat No. 45', '081298760005', 100, 'aktif'),
(6, '20261006', '0089123406', 'Nabila Zahra', 'P', 2, 'Malang', '2008-09-30', 'Jl. Alfalah No. 27', '081298760006', 25, 'aktif'),
(7, '20261007', '0089123407', 'Aditya Pratama', 'L', 3, 'Solo', '2008-01-14', 'Jl. Garu I No. 34', '081298760007', 60, 'aktif'),
(8, '20261008', '0078123408', 'Rian Hidayat', 'L', 5, 'Bogor', '2007-08-20', 'Jl. Pelajar No. 19', '081298760008', 40, 'aktif'),
(9, '20261009', '0067123409', 'Dinda Kirana', 'P', 6, 'Depok', '2006-12-05', 'Jl. Sudirman No. 67', '081298760009', 15, 'aktif'),
(10, '20261010', '0067123410', 'Fajar Ramadhan', 'L', 7, 'Tangerang', '2006-02-28', 'Jl. Bromo No. 8', '081298760010', 165, 'aktif');

-- 5. SEED PARENTS (Menghubungkan Niken Ayu Resdiana ke Siswa #1)
INSERT INTO `parents` (`id`, `user_id`, `student_id`, `nama_wali`, `hubungan`, `pekerjaan`, `no_telp`, `alamat`) VALUES
(1, 3, 1, 'NIKEN AYU RESDIANA', 'Wali', 'Wiraswasta', '081298760001', 'Jl. Melati No. 14'),
(2, NULL, 1, 'NIKEN AYU RESDIANA', 'Ibu', 'Wiraswasta', '081298760001', 'Jl. Melati No. 14'),
(3, NULL, 3, 'Bpk. Agus Setiawan', 'Ayah', 'PNS', '081298760003', 'Jl. STM No. 5'),
(4, NULL, 2, 'Ibu Ratna Dewi', 'Ibu', 'Pegawai Swasta', '081298760002', 'Jl. Sakti No. 88'),
(5, NULL, 5, 'Bpk. Budi Santoso', 'Ayah', 'Wiraswasta', '081298760005', 'Jl. Halat No. 45');

-- 6. SEED VIOLATION_CATEGORIES (24 Kategori Pelanggaran Standar)
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

-- 8. SEED VIOLATION_RECORDS (35 Catatan Pelanggaran Realistis Berelasi Valid)
INSERT INTO `violation_records` (`id`, `kode_transaksi`, `student_id`, `category_id`, `teacher_id`, `poin`, `tanggal_kejadian`, `waktu_kejadian`, `tempat_kejadian`, `keterangan`, `status_tindak_lanjut`) VALUES
-- Juli 2026
(1, 'TRX-202607-001', 10, 20, 5, 75, '2026-07-25', '07:10:00', 'Pemeriksaan Pintu Masuk', 'Membawa pisau lipat saku di dalam ransel sekolah saat razia awal tahun ajaran.', 'Diproses'),
(2, 'TRX-202607-002', 1, 4, 2, 5, '2026-07-28', '12:30:00', 'Area Kantin Sekolah', 'Membuang bungkus minuman kemasan sembarangan di meja kantin luar.', 'Selesai'),

-- Agustus 2026
(3, 'TRX-202608-001', 1, 1, 1, 5, '2026-08-04', '07:25:00', 'Gerbang Utama', 'Siswa hadir terlambat 15 menit melewati bel masuk sekolah.', 'Selesai'),
(4, 'TRX-202608-002', 8, 9, 3, 15, '2026-08-07', '10:15:00', 'Kantin Belakang', 'Ditemukan nongkrong di area kantin saat jam pelajaran Fisika berlangsung.', 'Selesai'),
(5, 'TRX-202608-003', 5, 17, 5, 50, '2026-08-11', '14:30:00', 'Luar Gerbang Samping', 'Terlibat perselisihan fisik antarsiswa di luar area gerbang sekolah sepulang sekolah.', 'Diproses'),
(6, 'TRX-202608-004', 10, 1, 2, 5, '2026-08-14', '07:22:00', 'Gerbang Utama', 'Terlambat masuk sekolah tanpa surat keterangan dari orang tua.', 'Selesai'),
(7, 'TRX-202608-005', 2, 1, 1, 5, '2026-08-18', '07:20:00', 'Gerbang Utama', 'Hadir terlambat 10 menit dikarenakan keterlambatan angkutan umum.', 'Selesai'),
(8, 'TRX-202608-006', 6, 6, 2, 10, '2026-08-20', '08:45:00', 'Ruang Kelas X MIPA 2', 'Makan makanan ringan di dalam kelas saat penyampaian materi berlangsung.', 'Selesai'),
(9, 'TRX-202608-007', 3, 14, 3, 25, '2026-08-25', '11:45:00', 'Toilet Belakang Lab', 'Membawa rokok elektrik (vape) di area toilet belakang sekolah.', 'Diproses'),
(10, 'TRX-202608-008', 7, 10, 1, 15, '2026-08-28', '10:30:00', 'Ruang Kelas X IPS 1', 'Mengoperasikan ponsel untuk bermain game tanpa izin saat pembelajaran KBM.', 'Selesai'),

-- September 2026
(11, 'TRX-202609-001', 1, 2, 1, 5, '2026-09-02', '07:30:00', 'Lapangan Upacara', 'Tidak mengenakan dasi dan ikat pinggang standar pada saat upacara hari Senin.', 'Selesai'),
(12, 'TRX-202609-002', 5, 7, 1, 10, '2026-09-08', '13:15:00', 'Ruang Kelas XII IPS 1', 'Terlambat memasuki ruang kelas 15 menit sesudah jam istirahat sholat Dzuhur.', 'Selesai'),
(13, 'TRX-202609-003', 10, 16, 1, 20, '2026-09-09', '07:15:00', 'Area Parkir Kendaraan', 'Mengendarai sepeda motor dengan knalpot bersuara bising di area gerbang masuk.', 'Diproses'),
(14, 'TRX-202609-004', 3, 9, 1, 15, '2026-09-12', '09:00:00', 'Parkiran Belakang', 'Meninggalkan jam pelajaran Sosiologi tanpa izin guru piket.', 'Diproses'),
(15, 'TRX-202609-005', 9, 1, 1, 5, '2026-09-15', '07:25:00', 'Gerbang Utama', 'Datang terlambat 15 menit ke sekolah.', 'Selesai'),
(16, 'TRX-202609-006', 6, 8, 4, 10, '2026-09-18', '08:00:00', 'Ruang Kelas X MIPA 2', 'Menggunakan perhiasan berlebihan yang tidak sesuai tata tertib seragam sekolah.', 'Selesai'),
(17, 'TRX-202609-007', 2, 6, 1, 10, '2026-09-22', '09:30:00', 'Ruang Kelas X MIPA 1', 'Makan camilan saat jam pembelajaran berlangsung.', 'Selesai'),
(18, 'TRX-202609-008', 8, 1, 1, 5, '2026-09-25', '07:22:00', 'Gerbang Utama', 'Terlambat hadir ke sekolah 10 menit.', 'Selesai'),
(19, 'TRX-202609-009', 7, 12, 5, 20, '2026-09-30', '08:15:00', 'Ruang Kelas X IPS 1', 'Membawa lembaran contekan terselip di bawah meja saat Ulangan Harian Sejarah.', 'Diproses'),

-- Oktober 2026
(20, 'TRX-202610-001', 3, 1, 5, 5, '2026-10-02', '07:20:00', 'Gerbang Utama', 'Terlambat hadir melewati bel pagi.', 'Selesai'),
(21, 'TRX-202610-002', 10, 14, 3, 25, '2026-10-06', '11:30:00', 'Kantin Belakang Lab', 'Menyimpan bungkus rokok di dalam saku celana seragam.', 'Diproses'),
(22, 'TRX-202610-003', 8, 4, 2, 5, '2026-10-10', '12:45:00', 'Koridor Kelas XI', 'Membuang botol plastik sembarangan di lantai koridor sekolah.', 'Selesai'),
(23, 'TRX-202610-004', 2, 5, 1, 5, '2026-10-14', '07:15:00', 'Gerbang Utama', 'Menggunakan sepatu warna putih garis merah (tidak standar hitam polos).', 'Selesai'),
(24, 'TRX-202610-005', 1, 9, 1, 15, '2026-10-15', '10:00:00', 'Gedung Olahraga', 'Tidak berada di dalam kelas saat jam pembelajaran Bahasa Inggris berlangsung.', 'Diproses'),
(25, 'TRX-202610-006', 5, 11, 3, 20, '2026-10-19', '11:15:00', 'Pagar Belakang Perpustakaan', 'Mencoba memanjat pagar sekolah untuk keluar area sebelum jam istirahat kedua.', 'Diproses'),
(26, 'TRX-202610-007', 6, 3, 1, 5, '2026-10-22', '07:20:00', 'Ruang Piket', 'Penggunaan pewarna rambut yang tidak sesuai ketentuan tata tertib sekolah.', 'Selesai'),
(27, 'TRX-202610-008', 7, 15, 1, 25, '2026-10-24', '13:00:00', 'Ruang Kelas X IPS 1', 'Menunjukkan sikap menantang instruksi guru saat dinasihati di ruang kelas.', 'Diproses'),
(28, 'TRX-202610-009', 3, 5, 2, 5, '2026-10-28', '07:18:00', 'Gerbang Utama', 'Memakai sandal ke dalam lingkungan sekolah tanpa alasan medis yang sah.', 'Selesai'),

-- November 2026
(29, 'TRX-202611-001', 5, 13, 1, 20, '2026-11-02', '14:00:00', 'Ruang Kelas XII IPS 1', 'Mencoret meja belajar menggunakan cat pilox.', 'Diproses'),
(30, 'TRX-202611-002', 9, 7, 4, 10, '2026-11-04', '13:10:00', 'Ruang Kelas XII MIPA 1', 'Terlambat kembali ke kelas lebih dari 15 menit setelah bel istirahat berbunyi.', 'Selesai'),
(31, 'TRX-202611-003', 1, 7, 1, 10, '2026-11-05', '13:15:00', 'Ruang Kelas X MIPA 1', 'Terlambat masuk kelas sesi siang setelah jam istirahat kedua.', 'Diproses'),
(32, 'TRX-202611-004', 4, 1, 1, 5, '2026-11-08', '07:22:00', 'Gerbang Utama', 'Terlambat 10 menit melewati bel pukul 07.15 WIB.', 'Selesai'),
(33, 'TRX-202611-005', 10, 23, 1, 40, '2026-11-10', '12:00:00', 'Kantin Bawah', 'Memaksa meminta uang saku dari adik kelas disertai ancaman verbal.', 'Diproses'),
(34, 'TRX-202611-006', 3, 6, 1, 10, '2026-11-12', '09:15:00', 'Ruang Kelas XI IPS 1', 'Makan mie instan di dalam ruang kelas saat guru menerangkan pelajaran.', 'Selesai'),
(35, 'TRX-202611-007', 8, 10, 5, 15, '2026-11-14', '10:45:00', 'Ruang Kelas XI IPS 1', 'Menonton video game di smartphone tanpa izin guru mata pelajaran.', 'Diproses');

-- 9. SEED NOTIFICATIONS (Log WhatsApp Realistis)
INSERT INTO `notifications` (`id`, `violation_record_id`, `student_id`, `parent_phone`, `message`, `provider`, `status`, `response_log`, `sent_by_user_id`, `sent_at`) VALUES
(1, 3, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Datang terlambat ke sekolah (< 15 menit)\nTanggal: 4 Agustus 2026\nPoin Pelanggaran: 5\nTotal Akumulasi Poin: 10\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001"}', 2, '2026-08-04 07:45:00'),
(2, 11, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Tidak mengenakan atribut seragam lengkap\nTanggal: 2 September 2026\nPoin Pelanggaran: 5\nTotal Akumulasi Poin: 15\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001"}', 2, '2026-09-02 08:00:00'),
(3, 24, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Meninggalkan kelas / bolos jam pelajaran\nTanggal: 15 Oktober 2026\nPoin Pelanggaran: 15\nTotal Akumulasi Poin: 30\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001"}', 2, '2026-10-15 10:35:00'),
(4, 31, 1, '081298760001', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa M. Farhan Pratama dari kelas X MIPA 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Terlambat masuk kelas setelah istirahat\nTanggal: 5 November 2026\nPoin Pelanggaran: 10\nTotal Akumulasi Poin: 40\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760001"}', 2, '2026-11-05 13:45:00'),
(5, 9, 3, '081298760003', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Dimas Arya Nugraha dari kelas XI IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Merokok / membawa rokok atau vape di sekolah\nTanggal: 25 Agustus 2026\nPoin Pelanggaran: 25\nTotal Akumulasi Poin: 25\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760003"}', 2, '2026-08-25 12:15:00'),
(6, 5, 5, '081298760005', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Bagus Wicaksono dari kelas XII IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Terlibat perkelahian / tawuran antarpelajar\nTanggal: 11 Agustus 2026\nPoin Pelanggaran: 50\nTotal Akumulasi Poin: 50\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760005"}', 2, '2026-08-11 15:00:00'),
(7, 33, 10, '081298760010', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Fajar Ramadhan dari kelas XII IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Pemalakan atau pemerasan uang terhadap teman\nTanggal: 10 November 2026\nPoin Pelanggaran: 40\nTotal Akumulasi Poin: 165\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760010"}', 2, '2026-11-10 12:30:00'),
(8, 29, 5, '081298760005', 'Yth. Bapak/Ibu Wali Siswa,\n\nKami informasikan bahwa siswa Bagus Wicaksono dari kelas XII IPS 1 telah tercatat melakukan pelanggaran sebagai berikut:\n\nJenis Pelanggaran: Merusak fasilitas sekolah / mencoret meja kursi\nTanggal: 2 November 2026\nPoin Pelanggaran: 20\nTotal Akumulasi Poin: 100\n\nMohon perhatian dan kerja sama Bapak/Ibu dalam mendukung pembinaan kedisiplinan siswa.\n\nTerima kasih.\nSistem Informasi Kedisiplinan Siswa', 'WAGateway-Simulator', 'simulated', '{"status":"success","code":200,"message":"[DEMO MODE] Pesan simulasi terkirim ke 081298760005"}', 2, '2026-11-02 14:45:00');

-- 10. SEED SYSTEM_SETTINGS
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('school_name', 'Sistem Informasi Kedisiplinan Siswa', 'Nama resmi sistem informasi institusi'),
('school_address', 'Jl. Pendidikan No. 10, Kompleks Administrasi Sekolah', 'Alamat resmi operasional sekolah'),
('school_phone', '(021) 7864321', 'Nomor telepon resmi sekolah'),
('school_email', 'admin@kedisiplinan.sch.id', 'Email resmi korespondensi sekolah'),
('school_lat', '-6.200000', 'Koordinat lintang instansi'),
('school_lng', '106.816666', 'Koordinat bujur instansi'),
('academic_year', '2026/2027', 'Tahun ajaran operasional aktif'),
('wa_gateway_provider', 'WAGateway-Simulator', 'Nama penyedia gateway WhatsApp (Fonnte / Wablas / Ruanggwa / Simulator)'),
('wa_gateway_api_key', 'DEMO_KEDISIPLINAN_API_KEY_2026', 'API Token kredensial gateway WhatsApp'),
('wa_gateway_sender', '081260112233', 'Nomor WhatsApp resmi pengirim notifikasi'),
('wa_demo_mode', '1', '1: Mode Demo Simulator Aktif, 0: Mode Produksi Live API Gateway');

SET FOREIGN_KEY_CHECKS = 1;
