# PROJECT_GUIDE — LaPhish

> Aturan khusus project ini. Prinsip kerja umum ada di `CLAUDE.global.md` (repo `ai-playbooks`).

## Identitas proyek
- **Judul:** LaPhish: Lapor dan Cek Phishing di Kampus
- **Nama sebelumnya:** LaporPhish. Diganti menjadi LaPhish pada 2026-11-21 agar mudah diingat, termasuk nama teknis (database `laphish`, URL `localhost/laphish`, repo `laphish`). Kode tiket tetap `LP-`.
- **Mata kuliah:** Pemrograman Berbasis Web, Proyek Akhir (individu)
- **Mulai:** 2026-11-20, durasi sekitar 2 minggu. Tenggat pasti: _belum dicatat_.
- **Pembuat (publik):** Arthur Pendragon, GitHub `Kingarthuria`, email noreply GitHub. Alias ini dipakai di semua tempat, termasuk laporan PDF.

## Deskripsi
Peringatan phishing di kampus biasanya hanya beredar di grup chat, cepat tenggelam, dan tidak bisa dicari ulang. LaPhish adalah aplikasi web PHP + MySQL tempat sivitas akademika **melaporkan** tautan, email, atau nomor mencurigakan, **mengecek** apakah suatu tautan pernah dilaporkan sebelum diklik, dan tempat unit TI **memantau** penanganan serta tren kasusnya.

## Target peran
| Fase | Peran | Keterangan |
|---|---|---|
| 1. Tugas kuliah (sekarang) | **Web Dev** | Fokus memenuhi syarat tugas. Tidak ada ML. |
| 2. Lanjutan (setelah dinilai) | Data Scientist | Model klasifikasi URL phishing dari dataset publik, diuji dengan laporan lokal yang sudah diverifikasi. |

## Syarat wajib dari dosen
- Berjalan di localhost (XAMPP/Laragon) dengan MySQL/MariaDB.
- CRUD lengkap yang terhubung ke database.
- Relasi antartabel (PK/FK) dan sudah dinormalisasi.
- Koneksi database dipisah ke file sendiri (`config.php` atau `.env`), dengan penanganan galat dan kueri aman (prepared statement).
- Presentasi mencakup latar belakang, 5 tahap SDLC, dan live demo CRUD dengan bukti perubahan di phpMyAdmin.

## Luaran
1. Laporan PDF berisi nilai manfaat dan artefak SDLC: analisis kebutuhan, mockup, ERD dan skema, arsitektur kode, tabel Black-Box Testing.
2. File `.sql` (struktur, relasi, dan data dummy) di folder `/database` dalam repo.
3. Repo GitHub publik berisi seluruh kode sumber.

## Ruang lingkup fase 1
**Masuk:**
- Halaman publik: beranda, formulir lapor, halaman cek indikator (pencocokan dengan data indikator yang tercatat), dan halaman lacak status dengan kode tiket.
- Login khusus admin (unit TI).
- Admin: CRUD laporan, indikator, kategori, dan kanal; ubah status penanganan; verifikasi indikator.
- Dashboard rekap: kartu dan tabel angka per status, kategori, kanal, dan bulan.
- Perkiraan sekitar 16 halaman (5 publik: beranda, lapor, sukses + tiket, cek, lacak; 11 admin: login, dashboard, daftar/detail/edit laporan, daftar/form indikator, daftar/form kategori, daftar/form kanal), ditambah file `.sql` berisi 30–50 laporan dummy.

**Tidak masuk (ditunda ke fase lanjutan):**
- Akun untuk pelapor dan halaman kelola akun admin.
- Grafik dashboard, data peran pelapor, dan status "Selesai".
- Model ML, skor risiko, dan pemanggilan API eksternal.
- Deploy online. Fase 1 cukup menuliskan rencananya di tahap SDLC 5.

## Keputusan desain awal
- URL, email, dan nomor disimpan di tabel `indicators` tersendiri, dengan kolom status verifikasi dari admin. Tujuannya agar nanti bisa diekspor sebagai data berlabel untuk fase 2.
- URL berbahaya ditampilkan dalam bentuk *defanged* (`hxxp://`, `[.]`), tidak pernah sebagai link yang bisa diklik.
- Semua output di-escape (`htmlspecialchars`). Semua kueri memakai prepared statement.
- Data di database dan di file `.sql` adalah **dummy**. Tidak ada nama, email, atau nomor asli.

## Keputusan tampilan (2026-11-21)
- **Arah desain A "Lensa" dipilih** dari tiga arah yang dibandingkan lewat prototipe statis: A (warna kampus tetap, hero berupa alat bedah alamat), B "Rambu" (kuning dominan, bergaya papan peringatan), dan C "Pesan" (hero berupa pesan chat). A dipilih karena paling sesuai dengan panduan visual awal, tidak butuh font baru, dan perombakannya paling kecil.
- **B tidak dipilih:** kuning menjadi warna utama (bertentangan dengan 02b: kuning hanya dekorasi); fontnya, Bahnschrift, hanya ada di Windows dan kemungkinan tidak boleh disertakan di repo publik (lisensi belum diverifikasi); dan perombakannya jauh lebih besar. **C tidak dipilih:** belum terbukti pada halaman admin dan membutuhkan font serif tambahan.
- **Yang diterapkan di aplikasi:** header dan footer publik terang; beranda dengan alat bedah alamat; hasil Cek dengan bedah alamat dan legenda; judul seksi bernomor di Lapor; Lacak dengan kode tiket kotak putus-putus, titik riwayat berwarna, dan legenda; kartu dan dashboard admin yang baru.
- **Perbaikan kontras yang ikut:** hijau status `#16A34A` menjadi `#15803D` (lencana hijau 3,3:1 menjadi 5,0:1) dan batas kolom isian menjadi `#8A839C` (sekitar 1,3:1 menjadi 3,6:1).
- **Aturan bedah alamat:** hanya menjelaskan *struktur* alamat (siapa pemilik domain) dan tidak menyatakan sebuah situs aman atau berbahaya. Alamat tetap ditampilkan *defanged* dan tidak pernah menjadi tautan. Aturan di JavaScript (beranda) dan di PHP (`url_segments()` di `includes/helpers.php`) berdiri sendiri; daftar akhiran Indonesia bertingkat (`ac.id`, `co.id`, dan sejenisnya) ada di kedua tempat dan perlu diubah bersamaan.
- **Penyesuaian lanjutan (2026-11-21), berdasarkan penilaian langsung:** tidak ada garis aksen tebal di tepi kartu atau pesan (bingkai tipis penuh); ikon hanya yang fungsional, tidak lagi pada lencana status, judul hasil, legenda, label kartu, dan tombol beranda; tanpa em dash dan titik tengah di teks yang terlihat; "Cara kerja" memakai nomor sedang berwarna gelap dengan garis tipis; kartu bedah alamat dibuat ramping (tanpa judul, pengantar, dan pesan penilaian); petunjuk dan contoh isian di Cek dan Lapor mengikuti jenis; tombol mata di kolom kata sandi login; footer hanya tautan "Masuk admin"; menu aktif sidebar memakai nada latar, bukan ungu; ikon merek identik di header, sidebar, dan login (gelap di latar terang, putih di latar gelap).
- **Pembagian halaman:** daftar Laporan dan Indikator di admin dibagi 10 baris per halaman (`PER_HALAMAN` di `includes/helpers.php`, parameter `halaman`). Daftar Kategori dan Kanal tidak dibagi karena pendek. Keputusan awal "tanpa paginasi" di 02b dicabut.
- **Rujukan:** perubahan terhadap panduan awal dicatat di `docs/02b_mockup.md` bagian 0 (tabel "Riwayat perubahan panduan"), dan hasil uji ulangnya di `docs/04_pengujian.md` bagian 8.
- **Cadangan sebelum perubahan** ada di `eksperimen_desain/cadangan_sebelum_A/`, dan prototipe statis arah A, B, dan C ada di `eksperimen_desain/`. Folder itu ada di `.gitignore` sehingga hanya tersimpan di komputer ini. Riwayat perubahan yang sebenarnya ada di git (commit berawalan `tampilan:`).

## Stack (diputuskan 2026-11-20)
- **Back-end:** PHP native dengan ekstensi PDO (prepared statement).
- **Front-end:** Bootstrap 5, font Plus Jakarta Sans, Bootstrap Icons. Palet warna dan gaya mengikuti panduan visual di `docs/02b_mockup.md` bagian 0, diperbarui 2026-11-21 ke arah desain A (konteks klien: Universitas Gunadarma, tanpa logo kampus). JavaScript ringan tanpa framework: `assets/js/app.js` dan `assets/js/bedah_alamat.js`.
- **Database:** MariaDB 10.4.32 (bawaan XAMPP).
- **Server lokal:** XAMPP yang sudah terpasang, dengan PHP 8.2.12. PHP tidak ada di PATH, jadi dipanggil dengan path lengkap dari folder XAMPP.

## Progres SDLC
- [x] Tahap 1: analisis kebutuhan, di `docs/01_analisis_kebutuhan.md` (disetujui 2026-11-20)
- [x] Tahap 2a: desain database, di `docs/02a_desain_database.md` (disetujui 2026-11-20)
- [x] Tahap 2b: mockup antarmuka, di `docs/02b_mockup.md` dan `docs/mockup/` (disetujui 2026-11-20; bagian 0 diperbarui 2026-11-21 ke tampilan akhir, sedangkan mockup hi-fi di `docs/mockup/` masih rancangan awal). Versi Figma yang bisa diedit: sebagian, lihat `docs/02b_mockup.md` bagian 5
- [x] Tahap 3: implementasi, di `docs/03_implementasi.md` (selesai 2026-11-20; 6 commit lokal, belum di-push)
- [x] Tahap 4: pengujian Black-Box, di `docs/04_pengujian.md` (58 kasus, 58 lulus, 2026-11-20). Diuji ulang 2026-11-21 setelah perubahan tampilan: 58 kasus awal lulus, ditambah 9 kasus baru (TC-59 sampai TC-67) untuk pembagian halaman dan perilaku formulir, total 67 kasus, 67 lulus (bagian 8 dokumen itu)
- [x] Tahap 5: rencana deploy dan pemeliharaan, di `docs/05_deploy_pemeliharaan.md` (disetujui 2026-11-21; skema deploy: shared hosting berbayar. Hosting gratis diuji langsung dan tidak lolos karena hak CREATE VIEW ditolak)
- [x] Laporan PDF: `docs/laporan/laporan.pdf` (45 halaman, selesai 2026-11-21; **memuat tampilan lama**, perlu dicetak ulang jika laporan harus mengikuti tampilan akhir). Sumbernya `docs/laporan/laporan.html` + `laporan.css` + `img/`, dicetak ulang dengan Microsoft Edge (cetak ke PDF)
- [x] Perubahan tampilan arah A (2026-11-21): diterapkan di aplikasi dan diuji ulang (67 kasus lulus), beserta penyesuaian lanjutan dan pembagian halaman. 02b dan 04_pengujian sudah diperbarui. Sudah di-commit lokal (commit berawalan `tampilan:` dan `docs:`), belum di-push. Belum diperbarui: mockup hi-fi di `docs/mockup/`, tangkapan layar dan PDF laporan, file Figma
- [ ] Pengumpulan: tautan laporan PDF, tautan `.sql` (folder `/database` di repo), tautan repo GitHub publik

## Belum diputuskan
- [ ] Tenggat pengumpulan dan jadwal presentasi.
- [ ] Apakah mockup hi-fi, tangkapan layar, dan PDF laporan dibuat ulang mengikuti tampilan akhir (arah A), atau laporan dibiarkan memuat rancangan awal dengan catatan.
- [x] Folder `eksperimen_desain/` (prototipe A, B, C dan cadangan berkas sebelum perubahan) **tidak** dimasukkan ke repo: sudah ada di `.gitignore` (2026-11-21). Folder itu bukan bagian aplikasi dan hanya ada di komputer ini.
- [ ] Apakah file Figma diperbarui ke tampilan akhir atau dibiarkan sebagai rancangan awal.

## Konvensi
- Bahasa Indonesia untuk README, komentar, dan pesan commit. Nama variabel, tabel, dan kolom memakai bahasa Inggris dengan format `snake_case`.
- Pesan commit diberi awalan fase atau tipe, misalnya `fase1: tambah tabel indikator`. Tanpa baris atribusi AI.
- Commit, push, dan membuat repo publik dilakukan hanya setelah disetujui per langkah.
- `config.php` atau `.env` yang berisi kredensial tidak di-commit. Yang di-commit adalah `config.example.php`.
- Server lokal hanya untuk komputer sendiri (bind ke `127.0.0.1`).
- `.claude/launch.json` (server pratinjau mockup di `127.0.0.1:5173`) berisi path absolut milik komputer ini, jadi folder `.claude/` wajib masuk `.gitignore` saat repo dibuat.
