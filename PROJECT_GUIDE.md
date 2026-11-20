# PROJECT_GUIDE — LaporPhish

> Aturan khusus project ini. Prinsip kerja umum ada di `CLAUDE.global.md` (repo `ai-playbooks`).

## Identitas proyek
- **Judul:** LaporPhish: Lapor dan Cek Phishing di Kampus
- **Mata kuliah:** Pemrograman Berbasis Web, Proyek Akhir (individu)
- **Mulai:** 2026-11-20, durasi sekitar 2 minggu. Tenggat pasti: _belum dicatat_.
- **Pembuat (publik):** Arthur Pendragon, GitHub `Kingarthuria`, email noreply GitHub. Alias ini dipakai di semua tempat, termasuk laporan PDF.

## Deskripsi
Peringatan phishing di kampus biasanya hanya beredar di grup chat, cepat tenggelam, dan tidak bisa dicari ulang. LaporPhish adalah aplikasi web PHP + MySQL tempat sivitas akademika **melaporkan** tautan, email, atau nomor mencurigakan, **mengecek** apakah suatu tautan pernah dilaporkan sebelum diklik, dan tempat unit TI **memantau** penanganan serta tren kasusnya.

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

## Stack (diputuskan 2026-11-20)
- **Back-end:** PHP native dengan ekstensi PDO (prepared statement).
- **Front-end:** Bootstrap 5, font Plus Jakarta Sans, Bootstrap Icons. Palet warna dan gaya mengikuti panduan visual di `docs/02b_mockup.md` bagian 0 (konteks klien: Universitas Gunadarma, tanpa logo kampus).
- **Database:** MariaDB 10.4.32 (bawaan XAMPP).
- **Server lokal:** XAMPP yang sudah terpasang, dengan PHP 8.2.12. PHP tidak ada di PATH, jadi dipanggil dengan path lengkap dari folder XAMPP.

## Progres SDLC
- [x] Tahap 1: analisis kebutuhan, di `docs/01_analisis_kebutuhan.md` (disetujui 2026-11-20)
- [x] Tahap 2a: desain database, di `docs/02a_desain_database.md` (disetujui 2026-11-20)
- [x] Tahap 2b: mockup antarmuka, di `docs/02b_mockup.md` dan `docs/mockup/` (disetujui 2026-11-20). Versi Figma yang bisa diedit: sebagian, lihat `docs/02b_mockup.md` bagian 5
- [x] Tahap 3: implementasi, di `docs/03_implementasi.md` (selesai 2026-11-20; belum di-commit)
- [ ] Tahap 4: pengujian Black-Box
- [ ] Tahap 5: rencana deploy dan pemeliharaan
- [ ] Laporan PDF: gabungan dokumen SDLC (01–05), nilai manfaat, screenshot mockup dan aplikasi, tabel Black-Box (luaran nomor 1)
- [ ] Pengumpulan: tautan laporan PDF, tautan `.sql` (folder `/database` di repo), tautan repo GitHub publik

## Belum diputuskan
- [ ] Tenggat pengumpulan dan jadwal presentasi.

## Konvensi
- Bahasa Indonesia untuk README, komentar, dan pesan commit. Nama variabel, tabel, dan kolom memakai bahasa Inggris dengan format `snake_case`.
- Pesan commit diberi awalan fase atau tipe, misalnya `fase1: tambah tabel indikator`. Tanpa baris atribusi AI.
- Commit, push, dan membuat repo publik dilakukan hanya setelah disetujui per langkah.
- `config.php` atau `.env` yang berisi kredensial tidak di-commit. Yang di-commit adalah `config.example.php`.
- Server lokal hanya untuk komputer sendiri (bind ke `127.0.0.1`).
- `.claude/launch.json` (server pratinjau mockup di `127.0.0.1:5173`) berisi path absolut milik komputer ini, jadi folder `.claude/` wajib masuk `.gitignore` saat repo dibuat.
