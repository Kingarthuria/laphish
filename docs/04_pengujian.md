# Tahap 4 — Testing (Black-Box)

Proyek: **LaporPhish: Lapor dan Cek Phishing di Kampus**
Dasar: kebutuhan FR-01 s.d. FR-13 dan NFR-01 s.d. NFR-10 di `01_analisis_kebutuhan.md`.

Status: **selesai** (2026-11-20). **58 kasus uji, 58 lulus.**

## 1. Metode

Pengujian Black-Box: aplikasi diuji dari luar, lewat input dan output yang dilihat pengguna, tanpa melihat isi kode. Setiap kasus dibandingkan antara hasil yang diharapkan dan hasil aktual.

| Bagian | Keterangan |
|---|---|
| Lingkungan | Windows 11, XAMPP: Apache, PHP 8.2.12, MariaDB 10.4.32. Aplikasi di `http://localhost/laporphish/` |
| Data awal | Hasil impor `database/laporphish.sql` (42 laporan, 31 indikator, 6 kategori, 6 kanal) |
| Cara uji form dan halaman | Permintaan HTTP otomatis (curl) ke Apache, sama seperti yang dikirim browser. Hasil dicek dari halaman yang tampil dan dari isi tabel di database |
| Cara uji interaksi JavaScript | Browser (Chromium): menambah baris indikator, teks bantuan kategori, dialog konfirmasi hapus, tampilan layar ponsel 375 px |
| Kode tiket dan ID | Kode tiket dibuat acak, jadi nilainya berbeda setiap kali diuji. Yang dicek adalah formatnya |
| Setelah uji | Database dikembalikan ke data awal dengan mengimpor ulang `database/laporphish.sql` |

Status: **Lulus** jika hasil aktual sama dengan hasil yang diharapkan.

## 2. Halaman publik

| ID | Kebutuhan | Skenario | Data uji | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC-01 | FR-01 | Membuka beranda | `index.php` | Halaman tampil dengan tiga fitur: Lapor, Cek, Lacak | HTTP 200, tiga kartu fitur tampil | Lulus |
| TC-02 | FR-02 | Mengirim laporan tanpa identitas | Kanal WhatsApp, kategori Pencurian akun, tanggal 2026-11-19, kronologi, 1 URL | Laporan tersimpan dengan pelapor kosong, satu indikator, dan status awal Baru. Diarahkan ke halaman sukses | Diarahkan ke `terkirim.php`. Laporan 42 → 43, pelapor anonim, 1 indikator, riwayat `baru` | Lulus |
| TC-03 | FR-02 | Pelapor dengan email yang sudah pernah dipakai | Email pelapor lama ditulis huruf besar, nama berbeda | Data pelapor lama dipakai ulang. Tidak ada pelapor baru dan nama lama tidak berubah | Laporan terhubung ke pelapor yang sama (ID 1). Jumlah pelapor tetap 16, nama tetap "Putri" | Lulus |
| TC-04 | FR-02, NFR-07 | Mengirim formulir kosong | Semua isian kosong | Laporan ditolak dengan pesan untuk setiap isian wajib | 5 pesan galat tampil, jumlah laporan tidak bertambah | Lulus |
| TC-05 | NFR-07 | Tanggal kejadian di masa depan | Tanggal besok (2026-11-21) | Ditolak | "Tanggal kejadian tidak boleh di masa depan." | Lulus |
| TC-06 | NFR-07 | Kronologi terlalu pendek | "pendek" | Ditolak | "Kronologi wajib diisi (minimal 10 karakter)." | Lulus |
| TC-07 | NFR-07 | Format URL salah | "bukan url" | Ditolak dengan contoh format | "Indikator 1: Format URL tidak dikenali. Contoh: https://contoh.example/login" | Lulus |
| TC-08 | FR-02, NFR-07 | Lebih dari 5 indikator dikirim langsung ke server (melewati batas di tampilan) | 6 URL | Ditolak oleh server | "Maksimal 5 indikator per laporan." | Lulus |
| TC-09 | FR-02, FR-10 | Indikator ganda dalam satu laporan dan indikator yang sudah tercatat | `HTTPS://WWW.BB-Uji-Tiga.example/x/`, `bb-uji-tiga.example/x`, dan nomor lama `0812-0000-1111` | Dua URL dianggap sama setelah dinormalisasi. Nomor lama dipakai ulang, bukan dibuat baru | 2 penghubung laporan–indikator. Indikator bertambah 1 (33 → 34) | Lulus |
| TC-10 | NFR-07 | Format email pelapor salah | "bukan-email" | Ditolak | "Format email pelapor tidak valid." | Lulus |
| TC-11 | FR-02 | Menambah baris indikator di formulir | Klik "Tambah indikator" 4 kali, lalu hapus satu baris | Berhenti di 5 baris dan tombol nonaktif. Setelah satu baris dihapus, tombol aktif lagi. Baris pertama tidak bisa dihapus | 5 baris, tombol nonaktif, keterangan "5 dari 5". Setelah dihapus: 4 baris, tombol aktif. Tombol hapus baris pertama tersembunyi | Lulus |
| TC-12 | FR-11 | Teks bantuan kategori di formulir | Pilih "Pencurian akun", lalu "APK berbahaya" | Deskripsi kategori yang dipilih tampil di bawah pilihan | Teks bantuan berganti sesuai deskripsi tiap kategori | Lulus |
| TC-13 | FR-03 | Kode tiket setelah laporan terkirim | Laporan dari TC-02 | Kode acak berformat `LP-XXXXXXXX` tampil | Kode tiket tampil dengan format yang sesuai | Lulus |
| TC-14 | FR-03 | Membuka halaman sukses tanpa mengirim laporan | `terkirim.php` langsung | Dialihkan ke formulir lapor | Dialihkan ke `lapor.php` | Lulus |
| TC-15 | FR-04 | Melacak laporan dengan kode tiket | Kode tiket dari TC-02 | Status terakhir dan riwayat tampil | Status "Baru", 1 riwayat | Lulus |
| TC-16 | FR-04 | Kode tiket ditulis huruf kecil | Kode tiket TC-02 dalam huruf kecil | Tetap ditemukan | Status "Baru" tampil | Lulus |
| TC-17 | FR-04, NFR-07 | Format kode tiket salah | "ABC" | Ditolak dengan contoh format | "Format kode tiket tidak sesuai. Contoh: LP-7F3K9QX2" | Lulus |
| TC-18 | FR-04 | Kode tiket tidak terdaftar | `LP-ZZZZZZZZ` | Pesan tidak ditemukan | "Kode tiket tidak ditemukan. Periksa kembali penulisannya." | Lulus |
| TC-19 | FR-04, NFR-09 | Melacak laporan yang mencantumkan email pelapor | Kode tiket dari TC-03 | Identitas pelapor tidak tampil | Email pelapor muncul 0 kali di halaman | Lulus |
| TC-20 | FR-05 | Cek URL yang terverifikasi phishing, ditulis berbeda | `HTTPS://WWW.bantuan-kuota-mahasiswa.example/form/` | Ditemukan setelah dinormalisasi: Terverifikasi phishing | "Terverifikasi phishing" | Lulus |
| TC-21 | FR-05 | Cek URL yang belum diverifikasi | URL dari TC-02 (status Belum) | Tercatat, masih ditinjau | "Tercatat, masih ditinjau" | Lulus |
| TC-22 | FR-05 | Cek URL yang sudah diperiksa dan aman | `portal-akademik.example/info` (Bukan phishing) | Sudah diperiksa, tidak terbukti phishing | "Sudah diperiksa, tidak terbukti phishing" | Lulus |
| TC-23 | FR-05 | Cek URL yang belum pernah dilaporkan, lalu melaporkannya | `https://bb-belum-pernah.example/z` | Belum ada data, ada tombol untuk melaporkan, dan formulir lapor terisi nilai tersebut | "Belum ada data", tombol "Laporkan nilai ini" tampil, formulir lapor terisi | Lulus |
| TC-24 | FR-05 | Cek nomor dengan format penulisan berbeda | `0812-0000-1111` (tersimpan `6281200001111`) | Ditemukan setelah dinormalisasi | "Terverifikasi phishing" | Lulus |
| TC-25 | FR-05, NFR-07 | Cek email dengan format salah | "bukan-email" | Ditolak | "Format email tidak valid." | Lulus |
| TC-26 | NFR-02 | Memasukkan skrip ke kolom Cek | `<script>alert(1)</script>` | Skrip tampil sebagai teks biasa, tidak dijalankan | Muncul mentah 0 kali, tampil ter-escape (`&lt;script&gt;`) | Lulus |
| TC-27 | NFR-03 | Tampilan URL pada hasil Cek | URL phishing dari TC-20 | Ditampilkan *defanged*, tidak bisa diklik | Tampil `bantuan-kuota-mahasiswa[.]example/form`, 0 tautan yang bisa diklik | Lulus |

## 3. Halaman admin

| ID | Kebutuhan | Skenario | Data uji | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC-28 | NFR-04 | Membuka halaman admin tanpa login | `admin/index.php` | Dialihkan ke halaman login | Dialihkan ke `admin/login.php` | Lulus |
| TC-29 | FR-06 | Login dengan password salah | Username `admin`, password salah | Ditolak tanpa menyebut bagian mana yang salah | "Username atau password salah" | Lulus |
| TC-30 | FR-06 | Login dengan akun demo | Akun di README | Masuk ke dashboard | Dashboard tampil dengan pesan "Selamat datang, Petugas TI." | Lulus |
| TC-31 | FR-06 | Logout lalu membuka admin lagi | Tombol Keluar, lalu `admin/index.php` | Keluar, dan halaman admin tidak bisa dibuka lagi | "Anda sudah keluar", lalu dialihkan ke login | Lulus |
| TC-32 | FR-07 | Angka di dashboard sesuai database | Data saat pengujian | Angka di kartu sama dengan hasil kueri langsung ke database | Kartu: total 45, Baru 6, Ditinjau 3, Valid 32, Tidak valid 4. Kueri database: angka yang sama | Lulus |
| TC-33 | FR-08 | Mencari laporan dengan kode tiket | Kode tiket TC-02 | Satu laporan ditemukan | 1 laporan | Lulus |
| TC-34 | FR-08 | Filter gabungan | Status Baru, kategori Pencurian akun, masuk 1–30 November 2026 | Jumlah hasil sama dengan kueri database | 3 laporan, kueri database juga 3 | Lulus |
| TC-35 | FR-08 | Mengubah laporan | Kategori, kanal, tanggal, dan kronologi baru | Perubahan tersimpan, waktu ubah tercatat | "Perubahan laporan … berhasil disimpan." Kategori 3, kanal 4, `updated_at` terisi | Lulus |
| TC-36 | NFR-07 | Mengubah laporan dengan tanggal masa depan | Tanggal besok | Ditolak | "Tanggal kejadian tidak boleh di masa depan." | Lulus |
| TC-37 | FR-08 | Menghapus laporan | Laporan dari TC-02 | Laporan, penghubung, dan riwayatnya terhapus. Indikator tetap ada | Pesan berhasil. Sisa penghubung 0, riwayat 0. Jumlah indikator tetap 34 | Lulus |
| TC-38 | FR-09 | Mengubah status dengan catatan | Status Valid, catatan "Terkonfirmasi phishing (uji)." | Riwayat bertambah, status terbaru berubah, catatan tampil di halaman Lacak | Riwayat 1 → 2, status terbaru `valid`, catatan tampil di Lacak | Lulus |
| TC-39 | FR-09, NFR-07 | Status di luar daftar dikirim langsung ke server | `selesai` | Ditolak | "Pilih status yang tersedia." | Lulus |
| TC-40 | FR-10 | Verifikasi indikator dari detail laporan | Indikator laporan TC-02 → Phishing | Status verifikasi, verifikator, dan waktunya tersimpan | Status `phishing`, verifikator ID 1, waktu terisi | Lulus |
| TC-41 | FR-10 | Menambah indikator tanpa laporan | `HTTPS://www.BB-Admin.example/masuk/`, status Phishing, catatan sumber | Tersimpan dalam bentuk normal | Tersimpan sebagai `bb-admin.example/masuk` | Lulus |
| TC-42 | FR-10 | Menambah indikator yang sudah tercatat | `bb-admin.example/masuk` | Ditolak dengan tautan ke data yang sudah ada | "Indikator ini sudah tercatat" dan tautan "Lihat data yang sudah ada" | Lulus |
| TC-43 | FR-10 | Mengubah indikator kembali ke Belum diverifikasi | Indikator TC-41 → Belum | Verifikator dan waktu verifikasi dikosongkan, nilai indikator tidak berubah | Status `belum`, verifikator dan waktu `NULL`, nilai tetap | Lulus |
| TC-44 | FR-10, FR-13 | Menghapus indikator yang masih menjadi bukti laporan | Indikator laporan TC-02 | Ditolak dengan pesan yang jelas, data tetap ada | "Indikator tidak bisa dihapus karena masih menjadi bukti di 1 laporan. Tandai sebagai "Bukan phishing" jika ternyata aman." | Lulus |
| TC-45 | FR-10 | Menghapus indikator yang tidak dipakai | Indikator TC-41 | Terhapus | "Indikator berhasil dihapus." | Lulus |
| TC-46 | FR-11 | Menambah dan mengubah kategori | "BB Kategori" → "BB Kategori 2" | Tersimpan dan berubah | Pesan berhasil untuk tambah dan ubah | Lulus |
| TC-47 | FR-11 | Menambah kategori dengan nama yang sudah ada | "Pencurian akun" | Ditolak | "Nama kategori sudah ada" | Lulus |
| TC-48 | FR-11, FR-13 | Menghapus kategori yang masih dipakai | "Pencurian akun" | Ditolak dengan jumlah laporan yang memakainya | "Kategori "Pencurian akun" tidak bisa dihapus karena masih dipakai 15 laporan." | Lulus |
| TC-49 | FR-11 | Menghapus kategori yang tidak dipakai | "BB Kategori 2" | Terhapus | "Kategori "BB Kategori 2" berhasil dihapus." | Lulus |
| TC-50 | FR-12 | Menambah, mengubah, menambah nama ganda, dan menghapus kanal | "BB Kanal" → "BB Kanal 2", nama ganda "WhatsApp" | Tambah dan ubah berhasil, nama ganda ditolak, kanal yang tidak dipakai terhapus | Semua pesan sesuai, termasuk "Nama kanal sudah ada" | Lulus |
| TC-51 | FR-12, FR-13 | Menghapus kanal yang masih dipakai | "WhatsApp" | Ditolak dengan jumlah laporan yang memakainya | "Kanal "WhatsApp" tidak bisa dihapus karena masih dipakai 15 laporan." | Lulus |

## 4. Keamanan, konfigurasi, dan tampilan

| ID | Kebutuhan | Skenario | Data uji | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC-52 | NFR-01 | Mencoba SQL injection di kolom cari | `' OR 1=1 -- ` | Dianggap teks biasa, tidak membocorkan semua data | HTTP 200, 0 laporan ditemukan | Lulus |
| TC-53 | NFR-10 | Menghapus lewat link GET | `admin/indikator.php?action=delete&id=…` | Tidak ada data yang terhapus | Indikator masih ada | Lulus |
| TC-54 | NFR-05, NFR-06 | File konfigurasi belum dibuat | `includes/config.php` dipindahkan sementara | Pesan petunjuk, bukan galat PHP | HTTP 500 dengan pesan "Konfigurasi belum dibuat" | Lulus |
| TC-55 | NFR-06 | Database tidak bisa dihubungi | Nama database di `config.php` diganti sementara | Pesan umum tanpa detail teknis | "Database tidak dapat dihubungi", 0 kata `SQLSTATE`/`PDOException` | Lulus |
| TC-56 | NFR-05 | Membuka file sensitif langsung dari browser | `includes/config.php`, `includes/db.php`, `database/laporphish.sql`, `.git/HEAD` | Akses ditolak | 403, 403, 403, dan 404 | Lulus |
| TC-57 | NFR-10 | Dialog konfirmasi sebelum menghapus | Tombol hapus kanal "Email", lalu pilih Batal | Dialog konfirmasi muncul. Jika dibatalkan, data tidak terhapus | Dialog "Hapus kanal "Email"?" muncul. Setelah Batal, halaman tetap dan kanal masih ada | Lulus |
| TC-58 | NFR-08 | Tampilan di layar ponsel | Lebar 375 px: beranda, lapor, cek, lacak, dashboard, daftar laporan, daftar indikator | Tidak ada halaman yang melebar ke samping | Lebar konten semua halaman 375 px, sama dengan lebar layar | Lulus |

## 5. Ringkasan

| Bagian | Jumlah kasus | Lulus |
|---|---|---|
| Halaman publik | 27 | 27 |
| Halaman admin | 24 | 24 |
| Keamanan, konfigurasi, dan tampilan | 7 | 7 |
| **Total** | **58** | **58** |

Semua FR-01 s.d. FR-13 dan NFR-01 s.d. NFR-10 punya minimal satu kasus uji.

## 6. Batasan pengujian

- Pengujian hanya di XAMPP (MariaDB 10.4) dan satu browser (Chromium). Kecocokan dengan MySQL 8 dan browser lain belum diuji.
- Tidak mencakup uji beban (banyak pengguna sekaligus) dan uji keamanan menyeluruh (penetration test).
- NFR-01 dan NFR-04 sebagian juga dibuktikan lewat pemeriksaan kode (semua kueri memakai prepared statement, password memakai `password_hash`), karena Black-Box hanya melihat perilaku dari luar.

## 7. Temuan dan perbaikan

| Temuan | Perbaikan | Hasil uji ulang |
|---|---|---|
| Saat TC-58, di layar ponsel kode tiket pada tabel daftar laporan admin terpotong menjadi dua baris (`LP-` dan sisanya). Fungsi tidak terganggu | Kelas `text-nowrap` ditambahkan pada sel kode tiket di `admin/laporan.php` | Kode tiket tampil dalam satu baris. Halaman tetap selebar 375 px, dan tabel bisa digeser ke samping di dalam kotaknya |
