# Tahap 2b — Rancangan Antarmuka (Wireframe)

Proyek: **LaporPhish: Lapor dan Cek Phishing di Kampus**
Dasar: kebutuhan di `01_analisis_kebutuhan.md` dan desain di `02a_desain_database.md`.

Rancangan dibuat dua tingkat, keduanya sebagai file HTML statis di folder `docs/mockup/`. Untuk laporan PDF, setiap halaman cukup di-screenshot dari browser.

1. **Wireframe (lo-fi):** abu-abu, hanya menunjukkan tata letak dan isi.
2. **Mockup (hi-fi):** memakai warna, font, ikon, dan gaya Bootstrap sesuai panduan visual di bagian 0. Menjadi acuan langsung untuk implementasi.

| File | Isi | Status |
|---|---|---|
| `docs/mockup/publik.html` | Wireframe 5 halaman publik (P1–P5) | Final |
| `docs/mockup/admin.html` | Wireframe 11 halaman admin (A1–A11) | Final |
| `docs/mockup/hifi_publik.html` | Mockup hi-fi halaman publik | Final |
| `docs/mockup/hifi_admin.html` | Mockup hi-fi halaman admin | Final |

## 0. Panduan visual (disetujui 2026-11-20)

Konteks klien: lingkungan **Universitas Gunadarma**. Warna diambil dari file logo resmi di gunadarma.ac.id dan dari warna teks situs resminya. Logo kampus **tidak** dipakai, karena repo bersifat publik dan aplikasi ini bukan layanan resmi kampus.

### Warna
| Peran | Kode | Sumber / pemakaian |
|---|---|---|
| Primer | `#653F99` | Ungu logo UG. Tombol utama, link, elemen aktif |
| Gelap | `#231F40` | Warna teks situs UG. Navbar, sidebar admin, judul, teks utama |
| Aksen | `#E5BD36` | Kuning api logo UG. **Hanya dekorasi** (garis bawah navbar, sorotan, ikon logo); tidak pernah dipakai untuk status |
| Latar | `#F7F5FB` | Putih keunguan untuk latar halaman |
| Teks redup | `#6F6B80` | Warna teks sekunder situs UG |
| Merah | `#DC2626` | Bahaya / terbukti phishing |
| Oranye tua | `#B45309` | Sedang diproses. Sengaja dibedakan dari kuning aksen |
| Hijau | `#16A34A` | Aman / tidak terbukti |
| Abu | `#6B7280` | Netral / tidak ada data |

Pemetaan warna status:

| Hal | Merah | Oranye tua | Hijau | Abu | Ungu primer |
|---|---|---|---|---|---|
| Hasil Cek / verifikasi indikator | Terverifikasi phishing | Tercatat, masih ditinjau | Tidak terbukti phishing | Belum ada data | – |
| Status laporan | Valid (terbukti phishing) | Ditinjau | – | Tidak valid | Baru |

Status laporan "Valid" diberi merah, bukan hijau, karena artinya laporan terbukti berisi phishing. Hijau hanya dipakai untuk arti "aman". Badge status selalu berisi **teks** (dan ikon), tidak mengandalkan warna saja. Kontras teks putih di atas `#653F99` sekitar 7,7:1 (lolos WCAG AA). Kuning `#E5BD36` tidak boleh dipakai sebagai warna teks di atas latar putih karena kontrasnya rendah.

### Tipografi, ikon, dan bentuk
| Elemen | Keputusan |
|---|---|
| Font | **Plus Jakarta Sans** (dipakai untuk semua teks). Saat implementasi, file font disimpan lokal di proyek agar demo tetap berjalan tanpa internet |
| Ikon | **Bootstrap Icons**, juga disimpan lokal saat implementasi |
| Identitas header | Ikon `bi-shield-check` + tulisan **LaporPhish**. Ikon berwarna kuning aksen, karena ungu `#653F99` di atas navbar `#231F40` kontrasnya terlalu rendah |
| Bentuk komponen | Sudut membulat sedang (radius sekitar 8 px), bayangan tipis pada kartu |
| Tata letak publik | Navbar atas (Beranda, Lapor, Cek, Lacak), tautan "Masuk admin" di footer |
| Tata letak admin | Sidebar kiri: Dashboard, Laporan, Indikator, Kategori, Kanal, lalu Keluar |
| Sapaan | Formal, memakai **"Anda"** |
| Lainnya | Mengikuti bawaan Bootstrap 5 (jarak, ukuran teks, tabel, pesan validasi form). Tanpa mode gelap |

## 1. Peta halaman

| Kode | Halaman | Akses | FR |
|---|---|---|---|
| P1 | Beranda | Publik | FR-01 |
| P2 | Formulir lapor | Publik | FR-02 |
| P3 | Laporan terkirim (kode tiket) | Publik | FR-03 |
| P4 | Cek indikator | Publik | FR-05 |
| P5 | Lacak status | Publik | FR-04 |
| A1 | Login admin | Publik (khusus admin) | FR-06 |
| A2 | Dashboard rekap | Admin | FR-07 |
| A3 | Daftar laporan (cari + filter) | Admin | FR-08 |
| A4 | Detail laporan (ubah status, verifikasi indikator, hapus) | Admin | FR-08, FR-09, FR-10 |
| A5 | Edit laporan | Admin | FR-08 |
| A6 | Daftar indikator | Admin | FR-10, FR-13 |
| A7 | Form indikator (tambah / edit verifikasi) | Admin | FR-10 |
| A8 | Daftar kategori | Admin | FR-11, FR-13 |
| A9 | Form kategori (tambah / edit) | Admin | FR-11 |
| A10 | Daftar kanal | Admin | FR-12, FR-13 |
| A11 | Form kanal (tambah / edit) | Admin | FR-12 |

Logout tidak punya halaman sendiri: tombol di menu admin langsung mengakhiri session lalu kembali ke A1.

## 2. Alur pengguna

**Pengunjung melapor**
P1 → P2 (isi formulir) → P3 (catat kode tiket) → P5 (lacak kapan saja dengan kode tiket)

**Pengunjung mengecek tautan**
P1 → P4 (pilih jenis, tempel nilai) → hasil. Jika "Belum ada data", ada tombol menuju P2.

**Admin menangani laporan**
A1 → A2 → A3 (cari/filter) → A4 (ubah status + catatan, verifikasi indikator) → kembali ke A3

**Admin mengelola data master**
Menu admin → A6/A8/A10 (daftar) → A7/A9/A11 (form). Hapus dilakukan dari halaman daftar lewat tombol POST dengan konfirmasi.

## 3. Catatan rancangan halaman publik

| Halaman | Keputusan |
|---|---|
| P2 | Indikator tampil 1 baris, dengan tombol "+ Tambah indikator" sampai maksimal 5 baris (JavaScript sederhana). Identitas pelapor ditaruh paling bawah dan ditandai opsional. Deskripsi kategori muncul di bawah dropdown kategori. |
| P3 | Kode tiket ditampilkan besar, disertai pesan untuk menyimpannya. Tidak ada cara memulihkan kode tiket yang hilang. |
| P4 | Dropdown jenis wajib dipilih. Nilai yang dicari ditampilkan kembali dalam bentuk *defanged*. Empat varian hasil mengikuti tabel logika Cek di `02a_desain_database.md` bagian 7. |
| P5 | Menampilkan kategori, kanal, tanggal kejadian, status terakhir, dan riwayat status beserta catatan admin. Tidak menampilkan identitas pelapor maupun indikator. |

## 4. Catatan rancangan halaman admin

| Halaman | Keputusan |
|---|---|
| A1 | Pesan galat login sama untuk username maupun password yang salah, agar tidak membocorkan username yang terdaftar. |
| A2 | Lima kartu (total dan empat status) yang bisa diklik untuk membuka A3 dengan filter status. Tiga tabel angka: per kategori, per kanal, dan per bulan (enam bulan terakhir, berdasarkan tanggal laporan masuk). Tanpa grafik. |
| A3 | Pencarian dan filter dalam satu baris. Diurutkan dari laporan terbaru. Tanpa paginasi, karena data demo di bawah 50 laporan. Tidak ada tombol tambah, karena laporan dibuat lewat P2. |
| A4 | Dua kolom: kiri berisi data laporan dan daftar indikator (verifikasi cepat lewat dropdown), kanan berisi form ubah status dan riwayat. Tombol Edit dan Hapus di kanan atas. |
| A5 | Hanya kategori, kanal, tanggal kejadian, dan kronologi yang bisa diubah. |
| A6 | Tombol Hapus nonaktif jika indikator masih terkait laporan. Kolom "Laporan" menampilkan jumlah laporan terkait. |
| A7 | Satu form untuk dua mode. Mode edit mengunci jenis dan nilai. Mode tambah menampilkan pesan "sudah tercatat" jika nilainya sudah ada. |
| A8–A11 | Di wireframe, daftar dan form ditampilkan berdampingan agar ringkas, tetapi di aplikasi tetap halaman terpisah. Tombol Hapus tetap aktif, dan penolakan datang dari database (RESTRICT) sehingga aturan FR-13 bisa didemokan. |
| Semua | Setiap aksi simpan atau hapus menampilkan pesan hasil (berhasil atau galat) di bagian atas halaman. Hapus selalu lewat form POST dengan dialog konfirmasi. |

## 5. Versi Figma (bisa diedit)

File: **LaporPhish — Mockup UI** di Drafts tim pribadi Figma  
Tautan: https://www.figma.com/design/jikPy6XFfwXOyj0iWMHWxF

Status per 2026-11-20: **sebagian**. Pembuatan terhenti karena batas panggilan Figma MCP paket Starter.

| Bagian | Status |
|---|---|
| Halaman: "Fondasi & Komponen", "Publik (P1–P5)", "Admin (A1–A11)" | Selesai |
| 23 variabel warna (koleksi "LaporPhish — Warna"), 14 text style Plus Jakarta Sans / JetBrains Mono, 2 efek bayangan | Selesai |
| 31 komponen ikon garis 24 px (`icon/...`) | Selesai |
| Komponen Button, Badge Status, Alert, Kartu Statistik | Selesai |
| Komponen Field, Hasil Cek, Navbar, Footer, Sidebar | Terhenti di tengah (perlu dicek dan dirapikan) |
| 16 layar P1–P5 dan A1–A11 | Belum dibuat |

Rencana cek perubahan: setelah file lengkap, struktur dan teks setiap layar disimpan sebagai snapshot acuan (`docs/mockup/figma_snapshot_v1.json`), lalu dibandingkan dengan isi file Figma saat diminta.
