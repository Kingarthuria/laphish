# Tahap 4 — Testing (Black-Box)

Proyek: **LaPhish: Lapor dan Cek Phishing di Kampus**
Dasar: kebutuhan FR-01 s.d. FR-13 dan NFR-01 s.d. NFR-10 di `01_analisis_kebutuhan.md`.

Status: **selesai** (2026-11-20). **58 kasus uji, 58 lulus.** Diuji ulang pada 2026-11-21 setelah nama aplikasi diganti menjadi LaPhish (URL, database, dan file SQL baru): 54 kasus HTTP serta TC-11 dan TC-12 tetap lulus. Diuji ulang lagi pada 2026-11-21 setelah perubahan tampilan (bagian 8): 58 kasus awal tetap lulus, ditambah 9 kasus baru (TC-59 sampai TC-67) untuk pembagian halaman dan perilaku formulir. **Total 73 kasus tercatat, 1 dicabut (TC-36), 72 berlaku, 72 lulus.** TC-35, TC-68, TC-69, dan TC-70 sampai TC-73 dijalankan 2026-11-21 setelah edit laporan dibatasi pada kategori dan kanal (lihat catatan di bagian 8).

## 1. Metode

Pengujian Black-Box: aplikasi diuji dari luar, lewat input dan output yang dilihat pengguna, tanpa melihat isi kode. Setiap kasus dibandingkan antara hasil yang diharapkan dan hasil aktual.

| Bagian | Keterangan |
|---|---|
| Lingkungan | Windows 11, XAMPP: Apache, PHP 8.2.12, MariaDB 10.4.32. Aplikasi di `http://localhost/laphish/` |
| Data awal | Hasil impor `database/laphish.sql` (42 laporan, 31 indikator, 6 kategori, 6 kanal) |
| Cara uji form dan halaman | Permintaan HTTP otomatis (curl) ke Apache, sama seperti yang dikirim browser. Hasil dicek dari halaman yang tampil dan dari isi tabel di database |
| Cara uji interaksi JavaScript | Browser (Chromium): menambah baris indikator, teks bantuan kategori, dialog konfirmasi hapus, tampilan layar ponsel 375 px |
| Kode tiket dan ID | Kode tiket dibuat acak, jadi nilainya berbeda setiap kali diuji. Yang dicek adalah formatnya |
| Setelah uji | Database dikembalikan ke data awal dengan mengimpor ulang `database/laphish.sql` |

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
| TC-35 | FR-08 | Mengubah kategori dan kanal laporan (dijalankan ulang 2026-11-21 setelah fitur dibatasi) | Kategori 3 dan kanal 4, ditambah kiriman POST buatan sendiri dengan tanggal `2020-01-01` dan kronologi lain | Kategori dan kanal tersimpan, tanggal dan kronologi tidak berubah, waktu ubah tercatat | Kategori 3, kanal 4, `updated_at` terisi. Tanggal tetap `2026-05-28` dan hash kronologi sama dengan sebelum kiriman | Lulus |
| TC-36 | NFR-07 | ~~Mengubah laporan dengan tanggal masa depan~~ | Dicabut 2026-11-21: tanggal kejadian tidak lagi bisa diubah admin, jadi kasus ini tidak berlaku | - | - | Dicabut |
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
| TC-56 | NFR-05 | Membuka file sensitif langsung dari browser | `includes/config.php`, `includes/db.php`, `database/laphish.sql`, `.git/HEAD` | Akses ditolak | 403, 403, 403, dan 404 | Lulus |
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

## 8. Uji ulang setelah perubahan tampilan (2026-11-21)

Tampilan aplikasi diubah mengikuti arah desain "Lensa": header dan footer publik terang, beranda dengan alat bedah alamat, hasil Cek dengan bedah alamat dan legenda, judul seksi di formulir Lapor, riwayat dan legenda di halaman Lacak, serta kartu dan dashboard admin yang baru. Setelah itu tampilan disesuaikan lagi beberapa kali berdasarkan penilaian langsung (garis aksen dan ikon dekoratif dibuang, petunjuk dan contoh isian mengikuti jenis, tombol mata di login, ikon merek disamakan, dan daftar Laporan serta Indikator dibagi per halaman). Logika status, pencarian, validasi, dan operasi tambah, ubah, hapus **tidak berubah**, dan struktur database tidak berubah. Yang berubah di sisi server hanya kueri daftar Laporan dan Indikator (kini menghitung jumlah baris yang cocok lalu memakai `LIMIT` dan `OFFSET`). Uji ulang dilakukan setelah setiap putaran perubahan untuk memastikan fungsi tidak rusak.

| Bagian | Keterangan |
|---|---|
| Lingkungan | Sama seperti bagian 1. Aplikasi di `http://127.0.0.1/laphish/` |
| Data awal | Impor `database/laphish.sql` (42 laporan, 31 indikator, 16 pelapor, 6 kategori, 6 kanal) |
| 54 kasus HTTP | Permintaan HTTP otomatis (curl) yang menjalankan kasus dengan data uji yang sama seperti bagian 2 sampai 4, termasuk memindahkan `config.php` sementara untuk TC-54 dan TC-55. Hasil dicek dari halaman dan dari isi tabel di database |
| 4 kasus browser | TC-11, TC-12, TC-57, dan TC-58 di Chromium, lewat pemicu JavaScript di halaman |
| Setelah uji | Database dikembalikan ke data awal dengan mengimpor ulang `database/laphish.sql`. Jumlah akhirnya sama dengan data awal |

**Hasil: 58 kasus awal, 58 lulus. Ditambah 9 kasus baru (TC-59 sampai TC-67), 9 lulus. Lalu edit laporan dibatasi pada kategori dan kanal: TC-35 diubah, TC-36 dicabut, dan TC-68 serta TC-69 ditambah, semuanya lulus. Lalu Lacak diberi blok isi laporan dan ditambah TC-70 sampai TC-72, lalu normalisasi nomor diperbaiki dan ditambah TC-73, semuanya lulus. Total 73 kasus tercatat, 1 dicabut, 72 berlaku, 72 lulus.** Ke-54 kasus HTTP dijalankan ulang setelah tiap putaran perubahan tampilan (empat kali), dan seluruhnya lulus (54 dari 54) pada setiap putaran yang memakai skrip uji yang sudah diperbaiki.

| Bagian | Jumlah kasus | Lulus |
|---|---|---|
| Halaman publik | 27 | 27 |
| Halaman admin | 24 | 24 |
| Keamanan, konfigurasi, dan tampilan | 7 | 7 |
| **Total** | **58** | **58** |

### Hal yang berbeda dari hasil aktual di bagian 2 sampai 4

Ini bukan kegagalan. Hasil yang diharapkan tetap terpenuhi, tetapi tampilannya atau data di sekitarnya berubah.

| Kasus | Perbedaan |
|---|---|
| TC-01 | Tiga fitur kini tampil sebagai tiga baris jalur (Lapor, Cek, Lacak), bukan tiga kartu. Beranda juga memuat alat bedah alamat |
| TC-27 | URL pada hasil Cek kini tampil sebagai bedah alamat dalam bentuk *defanged* (`bantuan-kuota-mahasiswa[.]example`). Tautan yang bisa diklik tetap 0 |
| TC-32 | Kartu dashboard sama dengan kueri database: total 45, Baru 6, Ditinjau 3, Valid 32, Tidak valid 4 (angka bergantung pada laporan yang dibuat oleh kasus sebelumnya dalam putaran uji yang sama) |
| TC-34 | Filter gabungan memberi 4 laporan, sama dengan kueri database (4). Pada data awal filter ini memberi 1 laporan; TC-02, TC-03, dan TC-09 menambah tiga laporan baru (status Baru, kategori Pencurian akun, tanggal masuk 2026-11-21) sebelum TC-34. Catatan lama (3) berasal dari putaran uji dengan urutan dan data yang berbeda |
| TC-48, TC-51 | Pesan menyebut 16 laporan (catatan lama: 15). Pada data awal kategori Pencurian akun dan kanal WhatsApp masing-masing dipakai 14 laporan; TC-03 dan TC-09 menambah dua laporan pada keduanya (laporan TC-02 sudah dipindah ke kategori dan kanal lain oleh TC-35). Angkanya sama dengan hasil kueri database saat itu |
| TC-58 | Lebar konten tujuh halaman (beranda, lapor, cek, lacak, dashboard, daftar laporan, daftar indikator) tetap 375 px. Kode tiket pada daftar laporan admin tampil satu baris pada seluruh 42 baris |

### Kasus tambahan (TC-59 sampai TC-67)

Kasus baru untuk perilaku yang ditambahkan setelah pengujian awal. Data uji: data awal (42 laporan, 31 indikator), diperiksa lewat permintaan HTTP dari halaman admin yang sudah masuk, dan lewat pemicu JavaScript di Chromium untuk perilaku di halaman.

| ID | Kebutuhan | Skenario | Data uji | Hasil yang diharapkan | Hasil aktual | Status |
|---|---|---|---|---|---|---|
| TC-59 | FR-08 | Daftar laporan dibagi halaman | `admin/laporan.php`, halaman 1 sampai 5 | 10 baris per halaman, tidak ada baris yang terlewat atau dobel | 10, 10, 10, 10, dan 2 baris. Total 42 baris, 42 unik. Keterangan "Laporan 1 sampai 10 dari 42" dan "Halaman 1 dari 5" | Lulus |
| TC-60 | FR-10 | Daftar indikator dibagi halaman | `admin/indikator.php`, halaman 1 sampai 4 | Sama seperti TC-59 | 10, 10, 10, dan 1 baris. Total 31 baris, 31 unik. Halaman terakhir ditulis "Indikator 31 dari 31" | Lulus |
| TC-61 | FR-08, NFR-07 | Nomor halaman di luar rentang atau tidak valid | `halaman=99`, `0`, `-3`, `abc` | Tidak galat, dibatasi ke halaman yang ada | `99` menampilkan halaman 5 (2 baris). `0`, `-3`, dan `abc` menampilkan halaman 1 (10 baris) | Lulus |
| TC-62 | FR-08 | Filter terbawa dan jumlah halaman mengikuti hasil | `status=valid`; `status=tidak_valid`; `q=zzzzzzzz` | Jumlah halaman dari hasil filter, tautan memuat filter, navigasi hanya bila lebih dari satu halaman | `status=valid`: 32 hasil, "Halaman 1 dari 4", keterangan "…dari 32 yang cocok (seluruhnya 42)", tautan Berikutnya `…?status=valid&halaman=2`, semua lencana Valid. Halaman 4: 2 baris tanpa Berikutnya. `tidak_valid`: 4 baris tanpa navigasi. `q=zzzzzzzz`: 0 baris, pesan "Tidak ada laporan yang cocok dengan pencarian ini.", tanpa navigasi | Lulus |
| TC-63 | FR-05 | Petunjuk dan contoh isian Cek mengikuti jenis | Pilih URL, Email, Nomor. Kirim email salah, nomor, dan URL salah | Petunjuk di atas formulir dan placeholder sesuai jenis, baik saat pilihan diganti maupun saat halaman dimuat ulang oleh server | Ketiganya sesuai opsi (placeholder `https://contoh.example/login`, `penipu@contoh.example`, `0812-3456-7890`), pada JavaScript dan pada hasil render server | Lulus |
| TC-64 | FR-02 | Contoh isian tiap baris indikator di Lapor mengikuti jenis | Ganti jenis baris 1 ke Email, tambah baris 2, ganti ke Nomor lalu kembali ke URL. Kirim email salah | Placeholder tiap baris sesuai jenisnya. Baris baru mulai dari URL. Baris lain tidak ikut berubah | Baris 1 `penipu@contoh.example`. Baris baru `https://contoh.example/login`. Baris 2 `0812-3456-7890`, lalu kembali ke URL, dan baris 1 tetap Email. Setelah kirim salah, jenis Email tetap terpilih dengan placeholder yang sesuai | Lulus |
| TC-65 | FR-06 | Tombol mata di kolom kata sandi login | Klik tombol dua kali | Kolom bergantian `password` dan `text`, label dan ikon berganti | Awal: `password`, label "Tampilkan kata sandi", ikon `bi-eye`. Setelah klik: `text`, label "Sembunyikan kata sandi", ikon `bi-eye-slash`, `aria-pressed="true"`, fokus tetap di kolom. Klik lagi: kembali `password` | Lulus |
| TC-66 | NFR-08 | Tata letak formulir Cek dan Lacak tidak bergeser saat galat | Kirim "https" di Cek dan "LP" di Lacak | Posisi kolom isian dan tombol sama sebelum dan sesudah pesan galat muncul | Cek: Jenis, Nilai, dan tombol sama-sama di 256 px sebelum dan sesudah galat. Lacak: kolom dan tombol di 256 px sebelum dan sesudah. Pesan galat hanya di bawah kolom isian | Lulus |
| TC-67 | NFR-08 | Ikon merek konsisten | Header publik, sidebar admin, halaman login | Glyph, ukuran ikon, dan ukuran teks merek sama | Ketiganya `bi-shield-check`. Ikon 22,4 px dan teks 19,2 px tebal 800 di header dan sidebar. Warna ikon `rgb(35,31,64)` di atas latar terang dan putih di sidebar gelap. Login memakai warna gelap yang sama | Lulus |
| TC-68 | FR-08, NFR-07 | Kategori tidak valid pada edit laporan | Kiriman `category_id=9999` | Ditolak, data tidak berubah | Pesan "Pilih kategori." tampil, kategori laporan tetap | Lulus |
| TC-69 | FR-08 | Halaman edit laporan tidak memuat kolom isian tanggal dan kronologi | Buka `admin/laporan_edit.php?id=1` | Hanya dua pilihan (kategori, kanal). Tanggal dan kronologi hanya teks | Jumlah isian `incident_date` dan `chronology` di halaman: 0. Tanggal dan kronologi tampil sebagai teks | Lulus |
| TC-70 | FR-04 | Lacak menampilkan isi laporan | Kode tiket `LP-8ZJUVN8K`, klik "Lihat laporan saya" | Blok tertutup secara bawaan. Setelah dibuka tampil kronologi dan semua indikator laporan | Kronologi tampil utuh. Indikator 2 dari 2 (URL dan Email). Tombol tampil tertutup lalu terbuka setelah diklik | Lulus |
| TC-71 | FR-04, NFR-03, NFR-09 | Nilai indikator di Lacak defanged dan identitas pelapor tidak tampil | Tiket `LP-8ZJUVN8K` (nilai indikator) dan `LP-3MNUDAKM` (pelapor punya nama dan email) | Tidak ada `http://` atau `https://` mentah. Nama dan email pelapor tidak ada di halaman | URL tampil `beasiswa-unggulan-2026[.]example/daftar`, email `info@beasiswa-unggulan-2026[.]example`. `http(s)://` mentah: 0. Nama dan email pelapor muncul 0 kali | Lulus |
| TC-72 | FR-04 | Kode tiket tidak ditemukan tidak menampilkan blok isi | `LP-ZZZZZZZZ` | Pesan galat, tidak ada blok `<details>` | Blok isi tidak muncul (0) | Lulus |
| TC-73 | FR-05, NFR-07 | Nomor telepon dengan berbagai format dan angka 0 berlebih setelah kode negara | Di Cek jenis Nomor: `+62 0858-0000-9999`, `0858-0000-9999`, `+62 858 0000 9999`, dan `0858-0000-9998` (tidak ada di data) | Tiga bentuk pertama ditemukan sebagai nomor yang sama. Bentuk keempat "Belum ada data". Fungsi normalisasi: `812-3456-7890`, `+1 202 555 0143`, `12345`, `abc` ditolak | Tiga bentuk pertama: "Tercatat, masih ditinjau" dan semuanya menjadi `6285800009999`. Bentuk keempat: "Belum ada data". Empat masukan tidak valid ditolak. Sebelum perbaikan, `+62 0812-3456-7890` tersimpan sebagai `62081234567890` | Lulus |

### Catatan dan batasan uji ulang

- **TC-57:** dialog bawaan peramban tidak ditekan. `window.confirm` diganti agar mencatat pesannya lalu menjawab Batal. Pesan yang tercatat adalah `Hapus kanal "Email"?`, halaman tidak berpindah, dan kanal Email masih ada. Ini menguji logika konfirmasi aplikasi, bukan kotak dialog bawaan peramban.
- **TC-11 dan TC-12:** dijalankan dengan memicu klik dan perubahan lewat JavaScript, bukan dengan interaksi langsung di layar.
- **Putaran pertama pelari uji:** 44 dari 54 kasus HTTP lulus. Sepuluh kegagalan itu berasal dari skrip uji, bukan dari aplikasi: pengalihan relatif (misalnya `../admin/login.php`) diselesaikan dengan basis yang salah sehingga pesan di halaman tujuan tidak terbaca, dan TC-55 memakai halaman yang saat GET tidak menyentuh database. Setelah skrip diperbaiki dan database dikembalikan ke data awal, putaran kedua lulus seluruhnya (54 dari 54). Tidak ada berkas aplikasi yang diubah di antara kedua putaran.
- **Cakupan:** perubahan tampilan diuji untuk perilaku, isi, dan lebar halaman. Halaman tiket (`terkirim.php`) hanya dibuka lewat TC-02 dan TC-13 untuk memeriksa format kode tiket; tampilan visualnya (di komputer maupun ponsel) tidak diperiksa ulang. Halaman login admin dan formulir tambah atau ubah (indikator, kategori, kanal, laporan) diuji fungsinya, bukan tampilannya di ponsel.
- **TC-35, TC-68, TC-69, TC-70 sampai TC-73:** dijalankan lewat permintaan HTTP (curl), TC-35 dengan sesi admin, dan TC-70 juga lewat klik di browser, dengan hasil dibaca langsung dari database. Data laporan yang dipakai dipulihkan ke nilai awal setelah uji. Belum dimasukkan ke skrip uji otomatis.
- **TC-59 sampai TC-67:** dijalankan lewat satu sesi admin di Chromium, dengan permintaan HTTP dari halaman dan pemicu JavaScript, belum dimasukkan ke skrip uji otomatis 54 kasus. TC-62 hanya menguji pencarian `zzzzzzzz` untuk hasil kosong dan dua filter status; kombinasi filter lain belum diuji di halaman berbeda. TC-67 menilai kesamaan sifat (glyph, ukuran, warna), bukan penampilan visual di setiap peramban.
- **Pembagian halaman dan kasus lama:** TC-33, TC-34, dan TC-52 menghitung baris pada halaman pertama. Ketiganya tetap sah karena hasilnya di bawah 10 baris, jadi semuanya tampil di halaman 1.
- Batasan di bagian 6 (satu peramban, tanpa uji beban dan uji keamanan menyeluruh) tetap berlaku.
