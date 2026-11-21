-- =============================================================
-- LaPhish: Lapor dan Cek Phishing di Kampus
-- Struktur database (8 tabel + 1 view) dan data contoh (dummy).
-- Kompatibel: MariaDB 10.4 (XAMPP) dan MySQL 8 (Laragon).
-- Cara pakai: impor file ini lewat phpMyAdmin (tab Import) atau
--   mysql -u root < database/laphish.sql
--
-- SEMUA DATA DI FILE INI FIKTIF: nama, email (@*.example), domain
-- (.example, dicadangkan RFC 2606), dan nomor telepon (pola 08xx-0000-xxxx).
-- Akun admin demo: username `admin`, password `LaPhish#Demo2026`
--   (hanya untuk demo lokal; tersimpan sebagai hash bcrypt).
-- =============================================================

CREATE DATABASE IF NOT EXISTS laphish CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE laphish;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW IF EXISTS v_report_status;
DROP TABLE IF EXISTS status_histories;
DROP TABLE IF EXISTS report_indicators;
DROP TABLE IF EXISTS indicators;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reporters;
DROP TABLE IF EXISTS channels;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS admins;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- STRUKTUR TABEL
-- -------------------------------------------------------------

-- Akun petugas unit TI (dibuat lewat file ini, tanpa halaman kelola akun)
CREATE TABLE admins (
  admin_id      INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jenis phishing (data master)
CREATE TABLE categories (
  category_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Media datangnya phishing (data master)
CREATE TABLE channels (
  channel_id INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Identitas pelapor (opsional). Email unik tetapi boleh NULL.
CREATE TABLE reporters (
  reporter_id INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(100) NULL,
  email       VARCHAR(150) NULL UNIQUE,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Laporan insiden. Status TIDAK disimpan di sini (lihat status_histories dan v_report_status).
CREATE TABLE reports (
  report_id     INT AUTO_INCREMENT PRIMARY KEY,
  ticket_code   CHAR(11) NOT NULL UNIQUE,
  reporter_id   INT NULL,
  category_id   INT NOT NULL,
  channel_id    INT NOT NULL,
  incident_date DATE NOT NULL,
  chronology    TEXT NOT NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES reporters (reporter_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_reports_category FOREIGN KEY (category_id) REFERENCES categories (category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_reports_channel  FOREIGN KEY (channel_id)  REFERENCES channels (channel_id)    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- URL, email, atau nomor yang dilaporkan (nilai sudah dinormalisasi)
CREATE TABLE indicators (
  indicator_id        INT AUTO_INCREMENT PRIMARY KEY,
  type                ENUM('url','email','phone') NOT NULL,
  value               VARCHAR(500) NOT NULL,
  verification_status ENUM('belum','phishing','bukan_phishing') NOT NULL DEFAULT 'belum',
  note                VARCHAR(255) NULL,
  verified_by         INT NULL,
  verified_at         DATETIME NULL,
  created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_indicator (type, value),
  CONSTRAINT fk_indicators_admin FOREIGN KEY (verified_by) REFERENCES admins (admin_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Penghubung laporan <-> indikator (banyak-ke-banyak)
CREATE TABLE report_indicators (
  report_id    INT NOT NULL,
  indicator_id INT NOT NULL,
  raw_value    VARCHAR(500) NOT NULL,
  PRIMARY KEY (report_id, indicator_id),
  CONSTRAINT fk_ri_report    FOREIGN KEY (report_id)    REFERENCES reports (report_id)       ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_ri_indicator FOREIGN KEY (indicator_id) REFERENCES indicators (indicator_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Riwayat status laporan (satu-satunya tempat status disimpan)
CREATE TABLE status_histories (
  history_id INT AUTO_INCREMENT PRIMARY KEY,
  report_id  INT NOT NULL,
  status     ENUM('baru','ditinjau','valid','tidak_valid') NOT NULL,
  note       TEXT NULL,
  admin_id   INT NULL,
  changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sh_report FOREIGN KEY (report_id) REFERENCES reports (report_id) ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT fk_sh_admin  FOREIGN KEY (admin_id)  REFERENCES admins (admin_id)   ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Status terakhir setiap laporan (tabel virtual, tidak menyimpan data)
CREATE VIEW v_report_status AS
SELECT h.report_id, h.status, h.changed_at
FROM status_histories h
WHERE h.history_id = (
  SELECT MAX(h2.history_id) FROM status_histories h2 WHERE h2.report_id = h.report_id
);

-- -------------------------------------------------------------
-- DATA CONTOH (DUMMY)
-- -------------------------------------------------------------
INSERT INTO admins (admin_id, username, password_hash, full_name, created_at) VALUES
  (1, 'admin', '$2y$10$Hns2OIdh4CFCILN8FH2fBuIZ9T2em0cyDHO35.gi.I7FBUxJZ9qx6', 'Petugas TI', '2026-06-01 07:00:00');

INSERT INTO categories (category_id, name, description) VALUES
  (1, 'Pencurian akun', 'Halaman login palsu yang meniru layanan kampus atau email untuk mencuri akun.'),
  (2, 'Hadiah/beasiswa palsu', 'Pesan yang menjanjikan hadiah, beasiswa, atau bantuan dana dengan meminta data pribadi atau biaya.'),
  (3, 'Lowongan palsu', 'Tawaran kerja atau magang palsu yang meminta data pribadi atau biaya pendaftaran.'),
  (4, 'APK berbahaya', 'File aplikasi (.apk) yang menyamar sebagai undangan, dokumen, atau info paket.'),
  (5, 'Tagihan palsu', 'Tagihan UKT, denda, atau biaya palsu yang meminta transfer ke rekening tidak resmi.'),
  (6, 'Lainnya', 'Jenis phishing yang belum tercantum di kategori lain.');
INSERT INTO channels (channel_id, name) VALUES
  (1, 'Email'),
  (2, 'WhatsApp'),
  (3, 'SMS'),
  (4, 'Media sosial'),
  (5, 'Situs web'),
  (6, 'Lainnya');
INSERT INTO reporters (reporter_id, name, email, created_at) VALUES
  (1, 'Putri', 'putri@kampus.example', '2026-06-01 21:04:55'),
  (2, 'Fajar', NULL, '2026-06-20 20:54:36'),
  (3, 'Bagus', 'bagus@kampus.example', '2026-07-03 03:56:56'),
  (4, 'Rizki', 'rizki@kampus.example', '2026-07-09 17:34:30'),
  (5, 'Maya', 'maya@kampus.example', '2026-07-12 17:55:15'),
  (6, 'Hendra', 'hendra@kampus.example', '2026-07-14 07:16:03'),
  (7, 'Hendra', NULL, '2026-07-15 21:25:22'),
  (8, 'Ayu', 'ayu@kampus.example', '2026-07-31 09:38:12'),
  (9, 'Andi', 'andi@kampus.example', '2026-08-10 10:48:27'),
  (10, 'Yoga', 'yoga@kampus.example', '2026-08-23 07:04:31'),
  (11, 'Nadia', NULL, '2026-08-27 11:58:19'),
  (12, 'Fajar', 'fajar@kampus.example', '2026-09-06 06:45:07'),
  (13, 'Bagus', NULL, '2026-10-04 00:33:41'),
  (14, 'Rina', NULL, '2026-10-06 05:44:01'),
  (15, 'Hendra', NULL, '2026-11-13 14:19:18'),
  (16, 'Rina', NULL, '2026-11-14 09:57:24');
INSERT INTO reports (report_id, ticket_code, reporter_id, category_id, channel_id, incident_date, chronology, created_at) VALUES
  (1, 'LP-ALFSKJP6', 1, 1, 2, '2026-05-28', 'Ada pesan lewat WhatsApp berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-06-01 21:04:55'),
  (2, 'LP-G84XGFLV', NULL, 5, 2, '2026-05-31', 'Menerima tagihan UKT lewat WhatsApp dengan nomor rekening pribadi, padahal pembayaran resmi lewat bank kampus.', '2026-06-02 10:31:21'),
  (3, 'LP-XTV6E34F', NULL, 5, 3, '2026-06-07', 'Menerima tagihan UKT lewat SMS dengan nomor rekening pribadi, padahal pembayaran resmi lewat bank kampus.', '2026-06-10 18:11:36'),
  (4, 'LP-P9BWMN34', 2, 6, 2, '2026-06-20', 'Menerima tautan pendek mencurigakan lewat WhatsApp dari nomor tidak dikenal tanpa keterangan.', '2026-06-20 20:54:36'),
  (5, 'LP-NGPJCF4A', NULL, 2, 4, '2026-06-19', 'Mendapat pesan lewat Media sosial bahwa saya lolos beasiswa dan diminta mengisi data pribadi serta membayar biaya administrasi.', '2026-06-20 22:52:53'),
  (6, 'LP-EYWNXAD8', NULL, 3, 4, '2026-06-23', 'Ada tawaran magang di BUMN lewat Media sosial, diminta transfer biaya seleksi dan mengirim foto KTM.', '2026-06-26 13:27:42'),
  (7, 'LP-Y53MX8XX', NULL, 3, 4, '2026-07-02', 'Ada tawaran magang di BUMN lewat Media sosial, diminta transfer biaya seleksi dan mengirim foto KTM.', '2026-07-02 11:57:35'),
  (8, 'LP-DCR5J6KU', 3, 1, 1, '2026-07-01', 'Ada pesan lewat Email berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-07-03 03:56:56'),
  (9, 'LP-AA6PX2NM', 3, 3, 4, '2026-07-08', 'Ada tawaran magang di BUMN lewat Media sosial, diminta transfer biaya seleksi dan mengirim foto KTM.', '2026-07-09 13:43:33'),
  (10, 'LP-ZWFLRJ6M', 4, 6, 3, '2026-07-07', 'Ada pesan berantai lewat SMS yang meminta kode OTP dengan alasan salah kirim.', '2026-07-09 17:34:30'),
  (11, 'LP-NPR66FMQ', 5, 6, 3, '2026-07-11', 'Ada pesan berantai lewat SMS yang meminta kode OTP dengan alasan salah kirim.', '2026-07-12 17:55:15'),
  (12, 'LP-55W9RTA2', 6, 1, 2, '2026-07-14', 'Menerima pesan lewat WhatsApp yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-07-14 07:16:03'),
  (13, 'LP-J38WD57H', 7, 4, 2, '2026-07-11', 'Menerima file .apk lewat WhatsApp yang katanya undangan pernikahan teman. Tidak saya buka.', '2026-07-15 21:25:22'),
  (14, 'LP-K3Q54DXF', 8, 6, 4, '2026-07-29', 'Ragu apakah tautan yang dibagikan di grup Media sosial ini resmi atau tidak, mohon dicek.', '2026-07-31 09:38:12'),
  (15, 'LP-3MNUDAKM', 9, 2, 1, '2026-08-07', 'Mendapat pesan lewat Email bahwa saya lolos beasiswa dan diminta mengisi data pribadi serta membayar biaya administrasi.', '2026-08-10 10:48:27'),
  (16, 'LP-454RHW5F', NULL, 5, 1, '2026-08-10', 'Ada pesan lewat Email soal denda perpustakaan yang harus dibayar lewat tautan dalam 1x24 jam.', '2026-08-13 09:37:22'),
  (17, 'LP-NWMW84RE', 3, 5, 2, '2026-08-17', 'Ada pesan lewat WhatsApp soal denda perpustakaan yang harus dibayar lewat tautan dalam 1x24 jam.', '2026-08-18 08:44:50'),
  (18, 'LP-9WYZ9HHG', NULL, 1, 2, '2026-08-16', 'Menerima pesan lewat WhatsApp yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-08-18 11:12:17'),
  (19, 'LP-3SR7CD6E', NULL, 1, 3, '2026-08-19', 'Ragu apakah tautan yang dibagikan di grup SMS ini resmi atau tidak, mohon dicek.', '2026-08-19 08:40:03'),
  (20, 'LP-8YQTNJ98', 10, 1, 1, '2026-08-22', 'Ada pesan lewat Email berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-08-23 07:04:31'),
  (21, 'LP-YQZRTSCG', 11, 5, 3, '2026-08-27', 'Ada pesan lewat SMS soal denda perpustakaan yang harus dibayar lewat tautan dalam 1x24 jam.', '2026-08-27 11:58:19'),
  (22, 'LP-VRHC8JLG', 12, 1, 1, '2026-09-03', 'Menerima pesan lewat Email yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-09-06 06:45:07'),
  (23, 'LP-2G7PEUYJ', 12, 6, 2, '2026-09-21', 'Ragu apakah tautan yang dibagikan di grup WhatsApp ini resmi atau tidak, mohon dicek.', '2026-09-22 09:59:47'),
  (24, 'LP-Z8JZD9NL', NULL, 1, 1, '2026-09-21', 'Menerima pesan lewat Email yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-09-22 16:32:09'),
  (25, 'LP-BE32TTJ8', 5, 3, 1, '2026-09-23', 'Ada tawaran magang di BUMN lewat Email, diminta transfer biaya seleksi dan mengirim foto KTM.', '2026-09-23 01:49:48'),
  (26, 'LP-QR75VGC4', 5, 1, 5, '2026-09-22', 'Ada pesan lewat Situs web berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-09-25 10:16:24'),
  (27, 'LP-N4DU37MX', 3, 2, 2, '2026-09-28', 'Menerima info hadiah undian Dies Natalis lewat WhatsApp, diminta klik tautan dan memasukkan nomor rekening.', '2026-10-02 02:03:05'),
  (28, 'LP-HTUSW2WF', 1, 6, 4, '2026-09-30', 'Ada pesan berantai lewat Media sosial yang meminta kode OTP dengan alasan salah kirim.', '2026-10-03 06:34:04'),
  (29, 'LP-5VAX5ACY', 13, 5, 1, '2026-10-04', 'Ada pesan lewat Email soal denda perpustakaan yang harus dibayar lewat tautan dalam 1x24 jam.', '2026-10-04 00:33:41'),
  (30, 'LP-7CCHJUX3', 14, 3, 4, '2026-10-03', 'Mendapat lowongan asisten laboratorium lewat Media sosial dengan gaji tinggi, diminta mengisi formulir di situs tidak resmi.', '2026-10-06 05:44:01'),
  (31, 'LP-N8K9C5F6', NULL, 1, 2, '2026-10-07', 'Ragu apakah tautan yang dibagikan di grup WhatsApp ini resmi atau tidak, mohon dicek.', '2026-10-10 16:13:46'),
  (32, 'LP-PX6CVCVK', NULL, 1, 1, '2026-10-21', 'Ada pesan lewat Email berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-10-22 00:25:57'),
  (33, 'LP-B2ZX4S45', NULL, 2, 2, '2026-10-31', 'Mendapat pesan lewat WhatsApp bahwa saya lolos beasiswa dan diminta mengisi data pribadi serta membayar biaya administrasi.', '2026-10-31 02:55:25'),
  (34, 'LP-G9CJR3PL', NULL, 3, 1, '2026-11-04', 'Mendapat lowongan asisten laboratorium lewat Email dengan gaji tinggi, diminta mengisi formulir di situs tidak resmi.', '2026-11-07 20:28:03'),
  (35, 'LP-8ZJUVN8K', NULL, 2, 2, '2026-11-11', 'Menerima info hadiah undian Dies Natalis lewat WhatsApp, diminta klik tautan dan memasukkan nomor rekening.', '2026-11-11 13:06:02'),
  (36, 'LP-VP96WX6C', NULL, 6, 3, '2026-11-10', 'Ada pesan berantai lewat SMS yang meminta kode OTP dengan alasan salah kirim.', '2026-11-11 18:26:21'),
  (37, 'LP-79GQL98S', NULL, 1, 5, '2026-11-11', 'Menerima pesan lewat Situs web yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-11-11 20:52:04'),
  (38, 'LP-WPM9YK97', NULL, 1, 2, '2026-11-12', 'Ada pesan lewat WhatsApp berisi peringatan kuota email penuh dan diminta memasukkan password di tautan yang diberikan.', '2026-11-12 17:22:29'),
  (39, 'LP-H44C9W8F', NULL, 3, 2, '2026-11-10', 'Ada tawaran magang di BUMN lewat WhatsApp, diminta transfer biaya seleksi dan mengirim foto KTM.', '2026-11-13 05:08:35'),
  (40, 'LP-U98Y4NP3', 15, 2, 4, '2026-11-10', 'Menerima info hadiah undian Dies Natalis lewat Media sosial, diminta klik tautan dan memasukkan nomor rekening.', '2026-11-13 14:19:18'),
  (41, 'LP-F25Y2F4R', 16, 1, 1, '2026-11-14', 'Menerima pesan lewat Email yang mengatasnamakan BAAK, diminta login ulang akun SSO melalui tautan karena akun akan dinonaktifkan.', '2026-11-14 09:57:24'),
  (42, 'LP-GK3JGUC3', 12, 5, 1, '2026-11-14', 'Menerima tagihan UKT lewat Email dengan nomor rekening pribadi, padahal pembayaran resmi lewat bank kampus.', '2026-11-14 11:14:31');
INSERT INTO indicators (indicator_id, type, value, verification_status, note, verified_by, verified_at, created_at) VALUES
  (1, 'url', 'portal-mahasiswa-update.example/verifikasi', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-03 06:04:55', '2026-06-01 21:04:55'),
  (2, 'phone', '6281200001111', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-03 06:04:55', '2026-06-01 21:04:55'),
  (3, 'url', 'pembayaran-ukt-online.example/tagihan', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-03 13:31:21', '2026-06-02 10:31:21'),
  (4, 'email', 'keuangan@pembayaran-ukt-online.example', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-11 16:11:36', '2026-06-10 18:11:36'),
  (5, 'phone', '6282100005555', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-11 16:11:36', '2026-06-10 18:11:36'),
  (6, 'url', 'tautan-pendek.example/xY9k', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-22 17:54:36', '2026-06-20 20:54:36'),
  (7, 'url', 'bantuan-kuota-mahasiswa.example/form', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-21 19:52:53', '2026-06-20 22:52:53'),
  (8, 'phone', '6281300002222', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-21 19:52:53', '2026-06-20 22:52:53'),
  (9, 'phone', '6285700003333', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-06-28 01:27:42', '2026-06-26 13:27:42'),
  (10, 'url', 'webmail-kampus.example/reset-password', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-04 07:56:56', '2026-07-03 03:56:56'),
  (11, 'url', 'karier-magang-bumn.example/apply', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-11 20:43:33', '2026-07-09 13:43:33'),
  (12, 'phone', '6287700008888', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-10 11:34:30', '2026-07-09 17:34:30'),
  (13, 'email', 'admin.sso@kampus-verify.example', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-15 03:16:03', '2026-07-14 07:16:03'),
  (14, 'phone', '6289600004444', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-16 09:25:22', '2026-07-15 21:25:22'),
  (15, 'phone', '6283800006666', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-07-16 09:25:22', '2026-07-15 21:25:22'),
  (16, 'url', 'portal-akademik.example/info', 'bukan_phishing', 'Situs resmi kampus, laporan keliru', 1, '2026-08-20 12:40:03', '2026-07-31 09:38:12'),
  (17, 'url', 'beasiswa-unggulan-2026.example/daftar', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-08-10 19:48:27', '2026-08-10 10:48:27'),
  (18, 'email', 'info@beasiswa-unggulan-2026.example', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-08-10 19:48:27', '2026-08-10 10:48:27'),
  (19, 'url', 'denda-perpustakaan.example/bayar', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-08-15 04:37:22', '2026-08-13 09:37:22'),
  (20, 'email', 'helpdesk@webmail-kampus.example', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-08-23 20:04:31', '2026-08-23 07:04:31'),
  (21, 'url', 'perpustakaan-kampus.example/katalog', 'bukan_phishing', 'Situs resmi kampus, laporan keliru', 1, '2026-10-11 10:13:46', '2026-09-22 09:59:47'),
  (22, 'url', 'lowongan-asisten-lab.example/form', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-10-06 21:44:01', '2026-10-06 05:44:01'),
  (23, 'url', 'hadiah-dies-natalis.example/klaim', 'phishing', 'Terkonfirmasi dari laporan pengguna', 1, '2026-11-01 13:55:25', '2026-10-31 02:55:25'),
  (24, 'url', 'verifikasi-krs-online.example/login', 'belum', NULL, NULL, NULL, '2026-11-11 20:52:04'),
  (25, 'email', 'promo@kuota-gratis.example', 'belum', NULL, NULL, NULL, '2026-11-13 14:19:18'),
  (26, 'url', 'siakad-login.example/masuk', 'belum', NULL, NULL, NULL, '2026-11-14 09:57:24'),
  (27, 'phone', '6285800009999', 'belum', NULL, NULL, NULL, '2026-11-14 09:57:24'),
  (28, 'url', 'form-wisuda-2026.example/daftar', 'belum', NULL, NULL, NULL, '2026-11-14 11:14:31'),
  (29, 'url', 'sso-kampus-login.example/auth', 'phishing', 'Sumber: peringatan resmi unit TI', 1, '2026-09-16 15:00:00', '2026-09-16 15:00:00'),
  (30, 'email', 'hadiah@beasiswa-palsu.example', 'phishing', 'Sumber: peringatan resmi', 1, '2026-09-16 16:00:00', '2026-09-16 16:00:00'),
  (31, 'phone', '6281900007777', 'phishing', 'Sumber: laporan dari bagian kemahasiswaan', 1, '2026-09-16 17:00:00', '2026-09-16 17:00:00');
INSERT INTO report_indicators (report_id, indicator_id, raw_value) VALUES
  (1, 1, 'HTTPS://portal-mahasiswa-update.example/verifikasi'),
  (1, 2, '0812-0000-1111'),
  (2, 3, 'https://pembayaran-ukt-online.example/tagihan/'),
  (3, 4, 'Keuangan@pembayaran-ukt-online.example'),
  (3, 3, 'https://pembayaran-ukt-online.example/tagihan/'),
  (3, 5, '0821-0000-5555'),
  (4, 6, 'https://www.tautan-pendek.example/xY9k'),
  (5, 7, 'https://bantuan-kuota-mahasiswa.example/form'),
  (5, 8, '0813 0000 2222'),
  (6, 9, '0857 0000 3333'),
  (7, 9, '0857-0000-3333'),
  (8, 10, 'https://webmail-kampus.example/reset-password'),
  (9, 9, '0857-0000-3333'),
  (9, 11, 'https://karier-magang-bumn.example/apply'),
  (10, 12, '0877-0000-8888'),
  (11, 6, 'https://tautan-pendek.example/xY9k'),
  (11, 12, '0877-0000-8888'),
  (12, 10, 'HTTPS://webmail-kampus.example/reset-password'),
  (12, 13, 'Admin.sso@kampus-verify.example'),
  (13, 14, '0896-0000-4444'),
  (13, 15, '0838-0000-6666'),
  (14, 16, 'https://portal-akademik.example/info'),
  (15, 17, 'https://beasiswa-unggulan-2026.example/daftar/'),
  (15, 18, 'Info@beasiswa-unggulan-2026.example'),
  (16, 19, 'https://denda-perpustakaan.example/bayar/'),
  (16, 4, 'keuangan@pembayaran-ukt-online.example'),
  (17, 4, 'Keuangan@pembayaran-ukt-online.example'),
  (18, 1, 'https://www.portal-mahasiswa-update.example/verifikasi'),
  (19, 16, 'https://www.portal-akademik.example/info'),
  (20, 20, 'helpdesk@webmail-kampus.example'),
  (21, 19, 'https://www.denda-perpustakaan.example/bayar'),
  (21, 3, 'https://pembayaran-ukt-online.example/tagihan'),
  (22, 1, 'https://portal-mahasiswa-update.example/verifikasi/'),
  (23, 21, 'https://www.perpustakaan-kampus.example/katalog'),
  (24, 10, 'HTTPS://webmail-kampus.example/reset-password'),
  (25, 9, '0857 0000 3333'),
  (25, 11, 'HTTPS://karier-magang-bumn.example/apply'),
  (26, 1, 'https://portal-mahasiswa-update.example/verifikasi'),
  (26, 20, 'Helpdesk@webmail-kampus.example'),
  (27, 7, 'https://www.bantuan-kuota-mahasiswa.example/form'),
  (27, 8, '0813 0000 2222'),
  (28, 12, '0877 0000 8888'),
  (29, 5, '0821-0000-5555'),
  (29, 4, 'Keuangan@pembayaran-ukt-online.example'),
  (29, 19, 'HTTPS://denda-perpustakaan.example/bayar'),
  (30, 22, 'https://www.lowongan-asisten-lab.example/form'),
  (31, 21, 'https://www.perpustakaan-kampus.example/katalog'),
  (32, 2, '0812 0000 1111'),
  (32, 20, 'helpdesk@webmail-kampus.example'),
  (32, 1, 'https://portal-mahasiswa-update.example/verifikasi/'),
  (33, 23, 'https://hadiah-dies-natalis.example/klaim/'),
  (33, 17, 'HTTPS://beasiswa-unggulan-2026.example/daftar'),
  (33, 7, 'https://www.bantuan-kuota-mahasiswa.example/form'),
  (34, 11, 'HTTPS://karier-magang-bumn.example/apply'),
  (34, 22, 'https://www.lowongan-asisten-lab.example/form'),
  (35, 17, 'https://beasiswa-unggulan-2026.example/daftar'),
  (35, 18, 'info@beasiswa-unggulan-2026.example'),
  (36, 12, '0877-0000-8888'),
  (37, 13, 'Admin.sso@kampus-verify.example'),
  (37, 24, 'https://www.verifikasi-krs-online.example/login'),
  (38, 20, 'helpdesk@webmail-kampus.example'),
  (39, 22, 'https://lowongan-asisten-lab.example/form'),
  (39, 11, 'HTTPS://karier-magang-bumn.example/apply'),
  (39, 9, '0857 0000 3333'),
  (40, 8, '0813 0000 2222'),
  (40, 17, 'https://beasiswa-unggulan-2026.example/daftar/'),
  (40, 25, 'promo@kuota-gratis.example'),
  (41, 26, 'HTTP://Siakad-login.example/masuk'),
  (41, 13, 'admin.sso@kampus-verify.example'),
  (41, 10, 'HTTPS://webmail-kampus.example/reset-password'),
  (41, 27, '0858-0000-9999'),
  (42, 4, 'Keuangan@pembayaran-ukt-online.example'),
  (42, 3, 'https://pembayaran-ukt-online.example/tagihan/'),
  (42, 28, 'https://form-wisuda-2026.example/daftar');
INSERT INTO status_histories (history_id, report_id, status, note, admin_id, changed_at) VALUES
  (1, 1, 'baru', NULL, NULL, '2026-06-01 21:04:55'),
  (2, 2, 'baru', NULL, NULL, '2026-06-02 10:31:21'),
  (3, 2, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-02 12:31:21'),
  (4, 1, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-02 15:04:55'),
  (5, 1, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-06-03 06:04:55'),
  (6, 2, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-06-03 13:31:21'),
  (7, 3, 'baru', NULL, NULL, '2026-06-10 18:11:36'),
  (8, 3, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-11 04:11:36'),
  (9, 3, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-06-11 16:11:36'),
  (10, 4, 'baru', NULL, NULL, '2026-06-20 20:54:36'),
  (11, 5, 'baru', NULL, NULL, '2026-06-20 22:52:53'),
  (12, 4, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-20 22:54:36'),
  (13, 5, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-21 13:52:53'),
  (14, 5, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-06-21 19:52:53'),
  (15, 4, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-06-22 17:54:36'),
  (16, 6, 'baru', NULL, NULL, '2026-06-26 13:27:42'),
  (17, 6, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-06-27 04:27:42'),
  (18, 6, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-06-28 01:27:42'),
  (19, 7, 'baru', NULL, NULL, '2026-07-02 11:57:35'),
  (20, 7, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-02 19:57:35'),
  (21, 7, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-07-03 02:57:35'),
  (22, 8, 'baru', NULL, NULL, '2026-07-03 03:56:56'),
  (23, 8, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-03 18:56:56'),
  (24, 8, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-07-04 07:56:56'),
  (25, 9, 'baru', NULL, NULL, '2026-07-09 13:43:33'),
  (26, 10, 'baru', NULL, NULL, '2026-07-09 17:34:30'),
  (27, 10, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-09 19:34:30'),
  (28, 9, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-10 01:43:33'),
  (29, 10, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-07-10 11:34:30'),
  (30, 9, 'valid', 'Terbukti penipuan. Terima kasih atas laporannya.', 1, '2026-07-11 20:43:33'),
  (31, 11, 'baru', NULL, NULL, '2026-07-12 17:55:15'),
  (32, 11, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-13 11:55:15'),
  (33, 11, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-07-14 01:55:15'),
  (34, 12, 'baru', NULL, NULL, '2026-07-14 07:16:03'),
  (35, 12, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-14 23:16:03'),
  (36, 12, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-07-15 03:16:03'),
  (37, 13, 'baru', NULL, NULL, '2026-07-15 21:25:22'),
  (38, 13, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-07-16 05:25:22'),
  (39, 13, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-07-16 09:25:22'),
  (40, 14, 'baru', NULL, NULL, '2026-07-31 09:38:12'),
  (41, 14, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-01 03:38:12'),
  (42, 14, 'tidak_valid', 'Tautan tersebut adalah layanan resmi kampus, bukan phishing.', 1, '2026-08-02 22:38:12'),
  (43, 15, 'baru', NULL, NULL, '2026-08-10 10:48:27'),
  (44, 15, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-10 13:48:27'),
  (45, 15, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-08-10 19:48:27'),
  (46, 16, 'baru', NULL, NULL, '2026-08-13 09:37:22'),
  (47, 16, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-13 16:37:22'),
  (48, 16, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-08-15 04:37:22'),
  (49, 17, 'baru', NULL, NULL, '2026-08-18 08:44:50'),
  (50, 18, 'baru', NULL, NULL, '2026-08-18 11:12:17'),
  (51, 18, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-18 23:12:17'),
  (52, 17, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-19 02:44:50'),
  (53, 19, 'baru', NULL, NULL, '2026-08-19 08:40:03'),
  (54, 17, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-08-19 10:44:50'),
  (55, 18, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-08-19 15:12:17'),
  (56, 19, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-20 00:40:03'),
  (57, 19, 'tidak_valid', 'Tautan tersebut adalah layanan resmi kampus, bukan phishing.', 1, '2026-08-20 12:40:03'),
  (58, 20, 'baru', NULL, NULL, '2026-08-23 07:04:31'),
  (59, 20, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-23 16:04:31'),
  (60, 20, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-08-23 20:04:31'),
  (61, 21, 'baru', NULL, NULL, '2026-08-27 11:58:19'),
  (62, 21, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-08-27 19:58:19'),
  (63, 21, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-08-27 21:58:19'),
  (64, 22, 'baru', NULL, NULL, '2026-09-06 06:45:07'),
  (65, 22, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-09-06 08:45:07'),
  (66, 22, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-09-06 14:45:07'),
  (67, 23, 'baru', NULL, NULL, '2026-09-22 09:59:47'),
  (68, 24, 'baru', NULL, NULL, '2026-09-22 16:32:09'),
  (69, 23, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-09-23 00:59:47'),
  (70, 25, 'baru', NULL, NULL, '2026-09-23 01:49:48'),
  (71, 24, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-09-23 08:32:09'),
  (72, 25, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-09-23 11:49:48'),
  (73, 25, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-09-23 17:49:48'),
  (74, 23, 'tidak_valid', 'Tautan tersebut adalah layanan resmi kampus, bukan phishing.', 1, '2026-09-24 22:59:47'),
  (75, 24, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-09-25 05:32:09'),
  (76, 26, 'baru', NULL, NULL, '2026-09-25 10:16:24'),
  (77, 26, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-09-25 23:16:24'),
  (78, 26, 'valid', 'Terbukti penipuan. Terima kasih atas laporannya.', 1, '2026-09-26 07:16:24'),
  (79, 27, 'baru', NULL, NULL, '2026-10-02 02:03:05'),
  (80, 27, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-02 05:03:05'),
  (81, 27, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-10-02 20:03:05'),
  (82, 28, 'baru', NULL, NULL, '2026-10-03 06:34:04'),
  (83, 28, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-03 22:34:04'),
  (84, 29, 'baru', NULL, NULL, '2026-10-04 00:33:41'),
  (85, 29, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-04 15:33:41'),
  (86, 29, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-10-05 03:33:41'),
  (87, 28, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-10-05 09:34:04'),
  (88, 30, 'baru', NULL, NULL, '2026-10-06 05:44:01'),
  (89, 30, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-06 17:44:01'),
  (90, 30, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-10-06 21:44:01'),
  (91, 31, 'baru', NULL, NULL, '2026-10-10 16:13:46'),
  (92, 31, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-11 03:13:46'),
  (93, 31, 'tidak_valid', 'Tautan tersebut adalah layanan resmi kampus, bukan phishing.', 1, '2026-10-11 10:13:46'),
  (94, 32, 'baru', NULL, NULL, '2026-10-22 00:25:57'),
  (95, 32, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-22 14:25:57'),
  (96, 32, 'valid', 'Terkonfirmasi phishing. Indikator sudah ditandai di fitur Cek.', 1, '2026-10-22 20:25:57'),
  (97, 33, 'baru', NULL, NULL, '2026-10-31 02:55:25'),
  (98, 33, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-10-31 11:55:25'),
  (99, 33, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-11-01 13:55:25'),
  (100, 34, 'baru', NULL, NULL, '2026-11-07 20:28:03'),
  (101, 34, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-08 02:28:03'),
  (102, 34, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-11-08 15:28:03'),
  (103, 35, 'baru', NULL, NULL, '2026-11-11 13:06:02'),
  (104, 36, 'baru', NULL, NULL, '2026-11-11 18:26:21'),
  (105, 37, 'baru', NULL, NULL, '2026-11-11 20:52:04'),
  (106, 36, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-12 05:26:21'),
  (107, 35, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-12 07:06:02'),
  (108, 37, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-12 12:52:04'),
  (109, 38, 'baru', NULL, NULL, '2026-11-12 17:22:29'),
  (110, 38, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-12 19:22:29'),
  (111, 35, 'valid', 'Terbukti penipuan. Terima kasih atas laporannya.', 1, '2026-11-12 20:06:02'),
  (112, 39, 'baru', NULL, NULL, '2026-11-13 05:08:35'),
  (113, 39, 'ditinjau', 'Laporan sedang diperiksa unit TI.', 1, '2026-11-13 08:08:35'),
  (114, 36, 'valid', 'Terkonfirmasi phishing dan sudah disosialisasikan lewat kanal resmi kampus.', 1, '2026-11-13 13:26:21'),
  (115, 40, 'baru', NULL, NULL, '2026-11-13 14:19:18'),
  (116, 41, 'baru', NULL, NULL, '2026-11-14 09:57:24'),
  (117, 42, 'baru', NULL, NULL, '2026-11-14 11:14:31');

