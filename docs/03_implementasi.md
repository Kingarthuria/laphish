# Tahap 3: Implementation (Development)

Proyek: **LaPhish: Lapor dan Cek Phishing di Kampus**
Dasar: `01_analisis_kebutuhan.md`, `02a_desain_database.md`, `02b_mockup.md`.

Status: **selesai** (langkah 3.1–3.6, 2026-11-20). Sudah di-commit dan di-push ke GitHub (2026-11-21).

## 1. Teknologi

| Bagian | Pilihan |
|---|---|
| Bahasa server | PHP 8.2 native (tanpa framework), ekstensi PDO |
| Database | MariaDB 10.4 (XAMPP), engine InnoDB, charset `utf8mb4` |
| Web server | Apache dari XAMPP |
| Tampilan | Bootstrap 5.3, Bootstrap Icons, font Plus Jakarta Sans, semuanya disimpan lokal di `assets/` agar demo berjalan tanpa internet |
| JavaScript | Hanya dua fungsi kecil: tambah/hapus baris indikator (P2) dan dialog konfirmasi hapus (admin) |

## 2. Struktur folder

```
Proyek_Akhir/
├── index.php              P1 Beranda
├── lapor.php              P2 Formulir lapor + proses kirim (POST)
├── terkirim.php           P3 Kode tiket
├── cek.php                P4 Cek indikator
├── lacak.php              P5 Lacak status
├── admin/
│   ├── login.php          A1   (logout.php: akhiri session, tanpa tampilan)
│   ├── index.php          A2 Dashboard
│   ├── laporan.php        A3 Daftar laporan
│   ├── laporan_detail.php A4 Detail + ubah status + verifikasi cepat + hapus
│   ├── laporan_edit.php   A5
│   ├── indikator.php      A6 (+ hapus)
│   ├── indikator_form.php A7 (tambah dan edit)
│   ├── kategori.php       A8 (+ hapus)
│   ├── kategori_form.php  A9
│   ├── kanal.php          A10 (+ hapus)
│   └── kanal_form.php     A11
├── includes/
│   ├── config.example.php Contoh konfigurasi (di-commit)
│   ├── config.php         Kredensial database asli (TIDAK di-commit)
│   ├── db.php             Koneksi PDO + penanganan galat
│   ├── helpers.php        escape output, defang, normalisasi indikator, kode tiket, pesan flash
│   ├── auth.php           Pengecekan session admin
│   ├── layout_publik_atas.php / layout_publik_bawah.php
│   └── layout_admin_atas.php  / layout_admin_bawah.php
├── assets/
│   ├── css/app.css        Panduan visual (variabel warna, font, badge, kartu)
│   ├── js/app.js
│   └── vendor/            Bootstrap, Bootstrap Icons, Plus Jakarta Sans
├── database/
│   └── laphish.sql     Struktur 8 tabel + view + data dummy
├── docs/                  Dokumen SDLC dan mockup
├── README.md
└── .gitignore
```

Setiap halaman yang punya form memproses POST-nya sendiri di bagian atas file, lalu mengalihkan (redirect) ke halaman yang sama dengan pesan flash. Pola ini sederhana dan mudah dijelaskan saat presentasi.

## 3. Urutan pengerjaan

| Langkah | Isi | Hasil yang bisa dicek |
|---|---|---|
| 3.1 | Persiapan: repo git lokal, `.gitignore`, aset lokal, akses Apache ke folder proyek | Halaman uji tampil di `http://localhost/laphish/` |
| 3.2 | `database/laphish.sql`: 8 tabel, view, akun admin demo, kategori/kanal awal, 30–50 laporan dummy (Juni–November 2026) | Diimpor ke MariaDB; jumlah baris tiap tabel dicek |
| 3.3 | `includes/` (config, db, helpers, auth, layout) + `assets/css/app.css` | Halaman kosong dengan navbar/sidebar sesuai mockup |
| 3.4 | Halaman publik P1–P5 | Alur lapor → tiket → lacak, dan cek indikator berjalan |
| 3.5 | Halaman admin A1–A11 | Semua CRUD berjalan, perubahan terlihat di phpMyAdmin |
| 3.6 | README (cara instal, akun demo, struktur) | Siap untuk tahap 4 (Black-Box testing) |

Setiap langkah ditunjukkan hasilnya dulu sebelum lanjut ke langkah berikutnya.

### Progres

- [x] **3.1 Persiapan** (2026-11-20): repo git (branch `main`, belum ada commit), `.gitignore`, aset lokal di `assets/vendor/`, junction `htdocs/laphish`, Apache hanya mendengarkan di `127.0.0.1` (port 80 dan 443).
- [x] **3.2 Database** (2026-11-20): `database/laphish.sql` diimpor ke MariaDB 10.4. Isi (dihitung dari kueri setelah impor): 1 admin, 6 kategori, 6 kanal, 16 pelapor, 42 laporan (Juni–November 2026; status Baru 3, Ditinjau 3, Valid 32, Tidak valid 4), 31 indikator (Phishing 24, Belum 5, Bukan phishing 2; 3 di antaranya ditambahkan admin tanpa laporan), 74 baris penghubung, 117 riwayat status. Semua tabel InnoDB dengan collation `utf8mb4_unicode_ci`. Uji aturan relasi lulus: RESTRICT pada kategori dan indikator, CASCADE saat laporan dihapus, SET NULL saat pelapor dihapus, view mengikuti riwayat terbaru, dan UNIQUE `(type, value)` menolak indikator ganda. Kompatibilitas dengan MySQL 8 belum diuji langsung karena MySQL 8 tidak terpasang.
- [x] **3.3 Fondasi kode** (2026-11-20): `includes/` (config, db, init, helpers, auth, layout publik dan admin), `assets/css/app.css`, `assets/js/app.js`, dan `.htaccess`. Semua file lolos `php -l`. Uji lewat Apache: halaman uji tampil dengan navbar dan sidebar sesuai mockup, semua aset lokal termuat (200), koneksi database membaca 42 laporan, `includes/` dan `database/` menolak akses langsung (403), `.git` tidak bisa diakses (404). `index.php` saat ini masih halaman uji sementara dan diganti Beranda di langkah 3.4.
- [x] **3.4 Halaman publik** (2026-11-20): `index.php` (P1), `lapor.php` (P2), `terkirim.php` (P3), `cek.php` (P4), `lacak.php` (P5). Cek dan Lacak memakai POST agar nilai yang dicek dan kode tiket tidak tersimpan di URL. Hasil uji fungsi lewat Apache: keempat hasil Cek muncul sesuai status verifikasi (termasuk input dengan variasi `HTTPS://WWW…/`), validasi Cek dan Lacak menolak input salah, input `<script>` ter-escape, formulir kosong menampilkan 5 pesan galat, tanggal masa depan ditolak, laporan valid tersimpan dalam satu transaksi (indikator lama dipakai ulang, duplikat dalam satu laporan disimpan sekali, pelapor dengan email yang sama dipakai ulang), lalu kode tiket tampil dan bisa dilacak dengan status Baru. Tambah baris indikator berhenti di 5. Database lokal sekarang berisi 1 laporan uji tambahan; impor ulang `database/laphish.sql` untuk kembali ke data awal.
- [x] **3.5 Halaman admin** (2026-11-20): `admin/login.php`, `logout.php`, `index.php` (A2), `laporan.php` (A3), `laporan_detail.php` (A4), `laporan_edit.php` (A5), `indikator.php` (A6), `indikator_form.php` (A7), `kategori.php` (A8), `kategori_form.php` (A9), `kanal.php` (A10), `kanal_form.php` (A11). Semua file lolos `php -l`. Hasil uji lewat Apache: halaman admin tanpa login dialihkan ke login; login salah ditolak dengan pesan umum; filter status, kata kunci, kategori, dan rentang tanggal berjalan; ubah status menambah baris riwayat dan catatannya tampil di halaman Lacak; status di luar daftar ditolak; verifikasi cepat mengisi verifikator dan waktu, dan menolak indikator yang bukan milik laporan; edit laporan mengisi `updated_at`; tambah indikator tersimpan ternormalisasi, duplikat ditolak dengan tautan ke data lama, kembali ke "Belum" mengosongkan verifikator; hapus indikator/kategori/kanal yang masih dipakai ditolak dengan pesan jumlah laporan (RESTRICT), sedangkan yang tidak dipakai berhasil dihapus; nama kategori/kanal ganda ditolak; hapus laporan ikut menghapus penghubung dan riwayat (CASCADE) tanpa menghapus indikator; logout mengakhiri session. Perbaikan selama uji: pesan "Anda sudah keluar" sebelumnya hilang karena cookie session terhapus, kini memakai ID session baru. Data uji sudah dibersihkan dengan impor ulang `database/laphish.sql`.
- [x] **3.6 README** (2026-11-20): `README.md` berisi deskripsi, fitur, teknologi, cara menjalankan di XAMPP, akun admin demo, keterangan data fiktif, struktur folder, ringkasan keamanan, tautan dokumentasi, pembuat (alias), keterangan bantuan AI, dan atribusi library. Semua tautan dan angka di README sudah dicocokkan dengan file dan isi database.

## 4. Keputusan persiapan (disetujui 2026-11-20)

| No | Hal | Keputusan | Alasan |
|---|---|---|---|
| K1 | Repo git | `git init` di folder proyek, branch `main`. Commit dibuat per tahap setelah disetujui | Commit dan push hanya setelah disetujui per langkah |
| K2 | `.gitignore` | `includes/config.php`, `.claude/`, dokumen tugas dari dosen (PDF deskripsi dan `Penjelasan Tambahan.txt`), file sistem (`Thumbs.db`, `.DS_Store`) | Kredensial dan path lokal tidak boleh masuk repo; dokumen tugas milik dosen tidak perlu dipublikasikan |
| K3 | Aset lokal | Mengunduh Bootstrap 5.3.3 (`bootstrap.min.css`, `bootstrap.bundle.min.js`), Bootstrap Icons 1.11.3 (CSS + font), dan Plus Jakarta Sans (satu file `woff2` variabel dari paket Fontsource) dari jsDelivr, beserta file LICENSE masing-masing. **Selesai 2026-11-20**, total sekitar 736 KB di `assets/vendor/` | Demo tetap berjalan tanpa internet |
| K4 | Akses proyek dari Apache | Membuat *junction* `htdocs/laphish` (di folder instalasi XAMPP) → folder proyek ini | Kode tetap di satu tempat, tanpa menyalin file |
| K5 | Apache hanya untuk komputer sendiri | Ubah `Listen 80` menjadi `Listen 127.0.0.1:80` di `httpd.conf` (dicadangkan dulu) | Sesuai aturan server lokal; berlaku juga untuk proyek lain di htdocs |
| K6 | Akun database | **Dibatalkan.** `config.example.php` memakai bawaan XAMPP (`root` tanpa password, host `127.0.0.1`), sehingga aplikasi langsung jalan setelah file itu disalin menjadi `config.php`. User database khusus dibahas di rencana deploy online (tahap 5) | Dosen bisa menjalankan aplikasi tanpa langkah tambahan yang rawan gagal |
| K7 | Akun admin demo | Username dan password demo dicatat di README dan file `.sql` (password tersimpan sebagai hash) | Dosen bisa login saat memeriksa; jelas ditandai sebagai akun demo |

## 5. Agar mudah diperiksa dan dijalankan dosen

| Hal | Ketentuan |
|---|---|
| File konfigurasi | `includes/config.example.php` di-commit dengan isi dan struktur yang **sama persis** dengan `config.php`, hanya nilainya bawaan XAMPP. Jika `config.php` belum ada, `db.php` menampilkan pesan jelas: "Salin includes/config.example.php menjadi includes/config.php". README menjelaskan langkah ini |
| Kompatibilitas database | `laphish.sql` ditulis agar bisa dipakai di MariaDB (XAMPP) maupun MySQL 8 (Laragon): collation `utf8mb4_unicode_ci`, tanpa sintaks khusus MariaDB. File diawali `CREATE DATABASE IF NOT EXISTS laphish` dan `USE laphish`, sehingga cukup diimpor sekali lewat phpMyAdmin. Sudah diuji di MariaDB 10.4; MySQL 8 belum diuji langsung |
| Nama folder bebas | Semua tautan halaman dan aset memakai path relatif, sehingga aplikasi tetap berjalan walaupun foldernya diberi nama lain di htdocs |

## 6. Mekanisme koneksi aplikasi–database (untuk presentasi SDLC 3)

```
halaman.php
  └─ require includes/init.php
        ├─ includes/db.php       → app_config() membaca includes/config.php
        │                        → db() membuat satu objek PDO (dipakai ulang)
        ├─ includes/helpers.php  → e(), url(), normalize_indicator(), defang(), generate_ticket(), flash()
        └─ includes/auth.php     → require_admin(), attempt_login(), logout()
```

1. **Konfigurasi terpisah.** `includes/config.php` mengembalikan array berisi host, port, nama database, user, password, dan charset. File ini tidak di-commit; repo hanya berisi `includes/config.example.php` dengan struktur yang sama. Jika `config.php` belum ada, aplikasi menampilkan petunjuk untuk menyalinnya.
2. **Membuat koneksi.** `db()` membentuk DSN `mysql:host=127.0.0.1;port=3306;dbname=laphish;charset=utf8mb4` lalu membuat objek PDO dengan tiga opsi:
   - `PDO::ERRMODE_EXCEPTION`: setiap galat SQL dilempar sebagai exception, sehingga tidak ada galat yang terlewat diam-diam.
   - `PDO::FETCH_ASSOC`: hasil kueri berupa array dengan nama kolom.
   - `PDO::ATTR_EMULATE_PREPARES = false`: prepared statement dikerjakan langsung oleh server database, bukan disimulasikan PHP.
   Koneksi dibuat sekali per permintaan halaman (disimpan di variabel `static`), lalu zona waktu sesi database disamakan dengan WIB.
3. **Penanganan galat.** Pembuatan koneksi dibungkus `try/catch (PDOException)`. Detail teknis dicatat ke log server dengan `error_log()`, sedangkan pengguna hanya melihat pesan umum ("Database tidak dapat dihubungi…"). Detail teknis baru ditampilkan di layar jika `debug` di konfigurasi diubah menjadi `true`.
4. **Keamanan kueri.** Setiap kueri yang memakai input pengguna ditulis dengan placeholder dan dijalankan lewat `prepare()` + `execute([...])`, contohnya di `attempt_login()`: `SELECT … FROM admins WHERE username = ?`. Semua output ke HTML melewati `e()` (`htmlspecialchars`).
5. **Perlindungan file.** `includes/.htaccess` dan `database/.htaccess` menolak akses langsung dari browser (403), dan `.htaccess` di akar proyek menyembunyikan daftar isi folder serta folder `.git`.

## 7. Catatan

- K5 (`Listen 127.0.0.1:80`) juga berlaku untuk proyek lain di folder `htdocs` XAMPP: tetap bisa dibuka di komputer sendiri, tetapi tidak lagi dari perangkat lain.
- MariaDB bawaan XAMPP menerima koneksi dari semua alamat (tidak ada `bind-address`). Bisa dibatasi ke `127.0.0.1` dengan cara yang sama seperti K5 jika diinginkan.
