# LaporPhish — Lapor dan Cek Phishing di Kampus

Aplikasi web untuk melaporkan dan mengecek tautan, email, atau nomor telepon yang dicurigai phishing di lingkungan kampus. Proyek akhir mata kuliah **Pemrograman Berbasis Web**, dibangun dengan PHP native dan MySQL/MariaDB, dan dijalankan di localhost (XAMPP).

Peringatan phishing di kampus biasanya hanya beredar di grup chat, cepat tenggelam, dan tidak bisa dicari ulang. LaporPhish menyediakan satu tempat bagi sivitas akademika untuk **melaporkan** pesan mencurigakan, **mengecek** apakah suatu tautan pernah dilaporkan sebelum diklik, dan bagi unit TI untuk **memantau** penanganan serta tren kasusnya.

## Fitur

**Pengunjung (tanpa login)**
- **Lapor**: kirim laporan dengan 1–5 indikator (URL, email, atau nomor). Identitas boleh dikosongkan.
- **Kode tiket**: setiap laporan mendapat kode acak `LP-XXXXXXXX` yang tidak bisa ditebak.
- **Cek**: periksa tautan, email, atau nomor. Hasilnya salah satu dari *Terverifikasi phishing*, *Tercatat, masih ditinjau*, *Sudah diperiksa, tidak terbukti phishing*, atau *Belum ada data*. Tautan selalu ditampilkan dalam bentuk *defanged* (tidak bisa diklik).
- **Lacak**: lihat status dan riwayat penanganan laporan dengan kode tiket.

**Admin (unit TI)**
- Dashboard rekap per status, kategori, kanal, dan bulan.
- Kelola laporan: cari, filter, lihat detail, ubah status beserta catatan, edit, dan hapus.
- Kelola indikator, kategori, dan kanal (CRUD). Data yang masih dipakai laporan tidak bisa dihapus.

## Teknologi

| Bagian | Teknologi |
|---|---|
| Server | PHP 8.2 native dengan PDO (prepared statement) |
| Database | MariaDB 10.4 / MySQL, 8 tabel InnoDB + 1 view, 3NF |
| Web server | Apache (XAMPP) |
| Tampilan | Bootstrap 5.3, Bootstrap Icons, font Plus Jakarta Sans. Semua disimpan lokal, sehingga aplikasi tetap berjalan tanpa internet |

## Cara menjalankan (XAMPP)

1. **Salin proyek** ke folder `htdocs` XAMPP, misalnya `htdocs/laporphish`. Nama folder bebas.
2. **Nyalakan** Apache dan MySQL lewat XAMPP Control Panel.
3. **Impor database**: buka phpMyAdmin (`http://localhost/phpmyadmin`), pilih tab **Import**, lalu pilih file `database/laporphish.sql`. File ini otomatis membuat database `laporphish` beserta data contohnya.
   Alternatif lewat terminal: `mysql -u root < database/laporphish.sql`
4. **Buat file konfigurasi**: salin `includes/config.example.php` menjadi `includes/config.php`. Nilai bawaannya sudah sesuai XAMPP (user `root` tanpa password). Jika file ini belum dibuat, aplikasi akan menampilkan petunjuk.
5. **Buka aplikasi** di `http://localhost/laporphish/`.

**Akun admin demo** (hanya untuk penggunaan lokal):

| Username | Password |
|---|---|
| `admin` | `LaporPhish#Demo2026` |

Halaman admin: `http://localhost/laporphish/admin/`

> Untuk mengembalikan database ke data awal, cukup impor ulang `database/laporphish.sql`.

## Data contoh

Semua data di `database/laporphish.sql` **fiktif**: 42 laporan (Juni–November 2026), 31 indikator, 6 kategori, dan 6 kanal. Domain memakai akhiran `.example` yang memang dicadangkan untuk contoh (RFC 2606), dan nomor telepon memakai pola fiktif `08xx-0000-xxxx`. Tidak ada nama, email, atau nomor milik orang sungguhan.

## Struktur folder

```
├── index.php, lapor.php, terkirim.php, cek.php, lacak.php   Halaman publik
├── admin/                Halaman admin (login, dashboard, CRUD)
├── includes/             Konfigurasi, koneksi database, fungsi bantu, layout
│   └── config.example.php   Contoh konfigurasi (salin menjadi config.php)
├── assets/               CSS, JavaScript, dan library lokal (vendor/)
├── database/             laporphish.sql (struktur + data contoh)
└── docs/                 Dokumentasi SDLC dan mockup
```

## Keamanan dasar

- Semua kueri yang menerima input memakai **prepared statement** (PDO).
- Semua output di-escape dengan `htmlspecialchars` untuk mencegah XSS.
- Password admin disimpan sebagai hash (`password_hash`), dan ID session diperbarui saat login.
- Penghapusan data hanya lewat form POST dengan dialog konfirmasi.
- Kredensial database ada di `includes/config.php` yang tidak ikut di-commit.
- Folder `includes/` dan `database/` tidak bisa dibuka langsung dari browser (`.htaccess`).

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [docs/01_analisis_kebutuhan.md](docs/01_analisis_kebutuhan.md) | Latar belakang, nilai manfaat, kebutuhan fungsional dan non-fungsional |
| [docs/02a_desain_database.md](docs/02a_desain_database.md) | ERD, kamus data, normalisasi, aturan relasi |
| [docs/02b_mockup.md](docs/02b_mockup.md) | Panduan visual, peta halaman, wireframe dan mockup (`docs/mockup/`) |
| [docs/03_implementasi.md](docs/03_implementasi.md) | Struktur kode, mekanisme koneksi database, progres implementasi |
| [docs/04_pengujian.md](docs/04_pengujian.md) | Tabel pengujian Black-Box (58 kasus uji) |

## Pembuat

**Arthur Pendragon** ([@Kingarthuria](https://github.com/Kingarthuria))

Proyek ini dikerjakan dengan bantuan asisten AI (Claude) untuk analisis, perancangan, penulisan kode, dan pengujian. Semua keputusan desain ditinjau dan disetujui oleh pembuat.

## Atribusi pihak ketiga

- [Bootstrap](https://getbootstrap.com/) 5.3.3 — lisensi MIT
- [Bootstrap Icons](https://icons.getbootstrap.com/) 1.11.3 — lisensi MIT
- [Plus Jakarta Sans](https://github.com/tokotype/PlusJakartaSans) — SIL Open Font License 1.1

Salinan lisensinya ada di folder `assets/vendor/` masing-masing.
