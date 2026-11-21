# Tahap 2b — Rancangan Antarmuka (Wireframe)

Proyek: **LaPhish: Lapor dan Cek Phishing di Kampus**
Dasar: kebutuhan di `01_analisis_kebutuhan.md` dan desain di `02a_desain_database.md`.

Rancangan dibuat dua tingkat, keduanya sebagai file HTML statis di folder `docs/mockup/`. Untuk laporan PDF, setiap halaman cukup di-screenshot dari browser.

1. **Wireframe (lo-fi):** abu-abu, hanya menunjukkan tata letak dan isi.
2. **Mockup (hi-fi):** memakai warna, font, ikon, dan gaya Bootstrap sesuai panduan visual di bagian 0. Menjadi acuan langsung untuk implementasi.

| File | Isi | Status |
|---|---|---|
| `docs/mockup/publik.html` | Wireframe 5 halaman publik (P1–P5) | Final |
| `docs/mockup/admin.html` | Wireframe 11 halaman admin (A1–A11) | Final |
| `docs/mockup/hifi_publik.html` | Mockup hi-fi halaman publik (P1–P5) | Final. Dibangun ulang 2026-11-21 mengikuti tampilan akhir (arah desain A), memakai aset yang sama dengan aplikasi |
| `docs/mockup/hifi_admin.html` | Mockup hi-fi halaman admin (A1–A11) | Final. Dibangun ulang 2026-11-21 mengikuti tampilan akhir (arah desain A) |

## 0. Panduan visual (disetujui 2026-11-20, diperbarui 2026-11-21)

Konteks klien: lingkungan **Universitas Gunadarma**. Warna diambil dari file logo resmi di gunadarma.ac.id dan dari warna teks situs resminya. Logo kampus **tidak** dipakai, karena repo bersifat publik dan aplikasi ini bukan layanan resmi kampus.

**Pembaruan 2026-11-21.** Setelah implementasi, tampilan dikaji ulang dengan membandingkan tiga arah desain lewat prototipe statis: A "Lensa" (tetap memakai warna kampus), B "Rambu" (kuning dominan, bergaya papan peringatan), dan C "Pesan" (hero berupa pesan chat). Arah **A** dipilih karena paling sesuai dengan panduan awal, tidak butuh font baru, dan risiko perombakannya paling kecil. Bagian ini sudah disesuaikan dengan tampilan yang kini berjalan. Perbedaan dari panduan awal dicatat di tabel "Riwayat perubahan panduan" di akhir bagian ini.

**Penyesuaian lanjutan 2026-11-21.** Setelah tampilan dicoba langsung, ada beberapa pola yang dinilai berlebihan dan terkesan template, lalu dibuang: garis aksen tebal di tepi kartu dan pesan, ikon yang hanya menghias label, angka langkah yang besar dan berwarna, tanda pisah em dash dan titik tengah di teks, serta kalimat pengisi yang tidak menambah informasi. Panduan di bawah sudah mencerminkan hasilnya. Prinsipnya: setiap unsur visual harus menyampaikan informasi yang belum disampaikan teks di dekatnya, dan bila tidak, dibuang.

### Warna
| Peran | Kode | Sumber / pemakaian |
|---|---|---|
| Primer | `#653F99` | Ungu logo UG. Tombol utama, link, nomor seksi di formulir Lapor |
| Gelap | `#231F40` | Warna teks situs UG. Sidebar admin, judul, teks utama, dan tepi kartu |
| Aksen | `#E5BD36` | Kuning api logo UG. **Hanya dekorasi dan penanda** (garis bawah menu aktif di header publik, sorotan domain pada bedah alamat, penanda jumlah terbanyak di dashboard); tidak pernah dipakai untuk status |
| Latar | `#F7F5FB` | Putih keunguan untuk latar halaman, header, dan footer |
| Teks redup | `#6F6B80` | Warna teks sekunder situs UG |
| Merah | `#DC2626` | Bahaya / terbukti phishing |
| Oranye tua | `#B45309` | Sedang diproses. Sengaja dibedakan dari kuning aksen |
| Hijau | `#15803D` | Aman / tidak terbukti. Lebih gelap dari rancangan awal (`#16A34A`) agar teks putih di lencana lolos kontras |
| Abu | `#6B7280` | Netral / tidak ada data |
| Biru | `#1D4ED8` | Status laporan Baru. Sengaja bukan ungu agar tidak terbaca sebagai tombol |
| Batas isian | `#8A839C` | Batas kolom isian, menu pilihan, dan tombol menu ponsel. Bawaan Bootstrap (`#DEE2E6`) terlalu pucat terhadap putih |

Pemetaan warna status:

| Hal | Merah | Oranye tua | Hijau | Abu | Biru |
|---|---|---|---|---|---|
| Hasil Cek / verifikasi indikator | Terverifikasi phishing | Tercatat, masih ditinjau | Tidak terbukti phishing | Belum ada data | – |
| Status laporan | Valid (terbukti phishing) | Ditinjau | – | Tidak valid | Baru |

Status laporan "Valid" diberi merah, bukan hijau, karena artinya laporan terbukti berisi phishing. Hijau hanya dipakai untuk arti "aman". Badge status selalu berisi **teks** (tanpa ikon), jadi artinya tidak bergantung pada warna saja. Kuning `#E5BD36` tidak boleh dipakai sebagai warna teks di atas latar putih karena kontrasnya rendah; yang di atasnya selalu teks gelap (`#231F40`, sekitar 8,7:1).

Kontras yang diukur dari aplikasi yang berjalan (2026-11-21):

| Pasangan | Kontras | Catatan |
|---|---|---|
| Teks putih di atas ungu `#653F99` | 7,71:1 | Tombol utama, nomor seksi |
| Teks putih di atas lencana merah, oranye, hijau, abu | 4,83 / 5,02 / 5,02 / 4,83:1 | Teks 12 px. Semua di atas batas 4,5:1 |
| Teks putih di atas lencana biru `#1D4ED8` | 6,70:1 | Status Baru |
| Teks redup `#6F6B80` di atas latar `#F7F5FB` | 4,74:1 | Lolos, tetapi tipis |
| Batas isian `#8A839C` di atas putih | 3,62:1 | Elemen non-teks, batas 3:1 |

### Tipografi, ikon, dan bentuk
| Elemen | Keputusan |
|---|---|
| Font | **Plus Jakarta Sans** (dipakai untuk semua teks). File font disimpan lokal di proyek agar demo tetap berjalan tanpa internet |
| Font untuk data | **Consolas** (cadangan Cascadia Mono dan monospace sistem) untuk alamat, kode tiket, dan nilai indikator. Tidak ada file font tambahan |
| Ikon | **Bootstrap Icons**, disimpan lokal. Dipakai hanya bila fungsional: ikon merek, tombol mata untuk melihat kata sandi di halaman login, dan tombol hapus baris indikator di formulir Lapor (tombol tanpa teks). **Tidak** dipakai pada menu, tombol bertulisan, lencana status, judul hasil, legenda, pesan, kolom isian, tautan, maupun halaman tiket, karena teks di sebelahnya sudah menyampaikan artinya |
| Identitas header | Ikon `bi-shield-check` + tulisan **LaPhish**, identik di header publik, sidebar admin, dan halaman login: glyph, ukuran (22,4 px), dan ukuran teks (19,2 px, tebal) sama. Warnanya satu skema logo satu warna: gelap `#231F40` di atas latar terang dan putih di atas latar gelap (sidebar), kontrasnya 15,65:1. Sebelumnya ikon ungu di header dan kuning di sidebar, dan itu dinilai tidak konsisten |
| Garis aksen | **Tidak dipakai.** Kotak hasil Cek dan pesan (berhasil, galat, info, peringatan) berbingkai tipis penuh (1,5 px) berwarna status di atas latar tipis, bukan garis tebal di satu sisi. Kartu dashboard tidak punya garis atas berwarna |
| Tanda baca | Tanpa em dash dan titik tengah (·) di teks yang terlihat. Pemisah memakai koma atau kata (misalnya "Diverifikasi oleh Petugas pada 10/08/2026 19.48"). Judul tab memakai `|` ("Cek indikator \| LaPhish"). Data kosong ditulis dengan kata atau dikosongkan, bukan tanda "–" |
| Bentuk komponen | Kartu bertepi tinta tipis (1,5 px `#231F40`), sudut 16 px, **tanpa bayangan**. Kolom isian, tombol, dan menu pilihan bersudut sedang (sekitar 8 px, bawaan Bootstrap). Satu-satunya bayangan geser ungu ada pada kartu alat bedah alamat di beranda |
| Judul | Tebal dan rapat (jarak huruf negatif). Judul beranda paling besar, diikuti judul halaman publik, lalu judul halaman admin |
| Tata letak publik | Header terang bergaris tipis (Beranda, Lapor, Cek, Lacak; menu aktif bergaris bawah kuning), isinya sejajar dengan konten halaman. Footer terang dengan tautan "Masuk admin" |
| Tata letak admin | Sidebar kiri gelap: Dashboard, Laporan, Indikator, Kategori, Kanal, lalu Keluar. Menu aktif ditandai nada latar yang lebih terang (putih transparan 12%) dengan huruf tebal, bukan ungu. Hover memakai nada yang sama tetapi lebih halus (6%) |
| Tabel | Kepala tabel berhuruf biasa (bukan kapital), berlatar ungu pucat `#F3F0F9`. Tanpa garis zebra. Daftar yang bisa panjang (Laporan dan Indikator) dibagi **10 baris per halaman** dengan navigasi Sebelumnya, nomor halaman, dan Berikutnya; filter ikut terbawa. Daftar master yang pendek (Kategori dan Kanal) tidak dibagi |
| Alamat dan nilai berbahaya | Selalu **defanged** (`hxxps://`, `[.]`), tidak pernah menjadi tautan. Untuk URL, hasil Cek dan beranda membedah alamat menjadi protokol, subdomain, domain pemilik situs (disorot kuning), port, dan jalur |
| Sapaan | Formal, memakai **"Anda"** |
| Lainnya | Mengikuti bawaan Bootstrap 5 (jarak, ukuran teks, pesan validasi form). Tanpa mode gelap |

### Riwayat perubahan panduan (2026-11-21)

| Bagian | Rancangan awal (2026-11-20) | Tampilan akhir | Alasan |
|---|---|---|---|
| Navbar publik | Gelap `#231F40` dengan garis bawah kuning 4 px | Header terang bergaris tipis, menu aktif bergaris bawah kuning | Arah A |
| Ikon merek | Kuning aksen di header (rancangan awal), lalu ungu di header dan kuning di sidebar (versi pertama arah A) | Satu glyph, ukuran, dan skema warna di header, sidebar, dan login: gelap di latar terang, putih di latar gelap | Warna yang berbeda di tiap tempat dinilai tidak konsisten. Satu warna tidak bisa terbaca di kedua latar, jadi dipakai versi terbalik |
| Footer publik | Gelap | Terang bergaris atas tipis | Selaras dengan header |
| Hijau | `#16A34A` | `#15803D` | Teks putih 12 px di lencana hijau hanya 3,3:1; kini 5,0:1 |
| Batas isian | Bawaan Bootstrap `#DEE2E6` (sekitar 1,3:1) | `#8A839C` (3,6:1) | Bentuk kolom isian hampir tak terlihat |
| Kartu | Tanpa tepi, bayangan tipis, radius sekitar 8 px | Tepi tinta 1,5 px, radius 16 px, tanpa bayangan | Arah A |
| Kepala tabel | Huruf kapital kecil | Huruf biasa, latar ungu pucat | Lebih mudah dibaca |
| Fungsi tampilan baru | – | Alat bedah alamat (beranda), bedah alamat dan legenda (Cek), legenda dan penanda "terkini" (Lacak), nomor seksi (Lapor), lencana status pada kartu dashboard, penanda jumlah terbanyak, pembagian halaman di daftar admin, tombol mata di kolom kata sandi login | Menjelaskan siapa pemilik sebuah alamat dan arti setiap status, serta memudahkan membaca daftar yang panjang |
| Font data | `Courier New` | Consolas | Lebih konsisten dan terbaca |
| Garis aksen tebal | Garis kiri 5 sampai 6 px pada kotak hasil Cek dan pesan, garis atas 6 px pada kartu dashboard, pita kuning 4 px di dasar halaman login, garis atas dan angka besar ungu pada "Cara kerja" | Bingkai tipis penuh pada kotak hasil dan pesan. Kartu dashboard, halaman login, dan "Cara kerja" tanpa garis aksen berwarna | Garis aksen satu sisi dinilai hiasan yang terkesan template |
| Ikon dekoratif | Ikon di setiap lencana status, judul hasil Cek, legenda, label dan judul kartu dashboard, tombol dan tautan beranda (termasuk panah), petunjuk Cek, serta centang besar di halaman tiket | Dihapus. Ikon tersisa hanya yang fungsional (lihat tabel Tipografi) | Teks di sebelahnya sudah menyampaikan artinya |
| Cara kerja (beranda) | Daftar bernomor, lalu angka besar ungu dengan garis atas ungu | Nomor sedang (24 px, tebal) berwarna gelap dengan garis tipis gelap di atas, sama dengan garis daftar Cek, Lapor, Lacak | Angka besar berwarna terlalu mencolok, daftar biasa terlalu datar. Urutan tetap terbaca sekilas tanpa menambah beban membaca |
| Kartu bedah alamat (beranda) | Judul, kalimat pengantar, pesan penilaian merah, dan keterangan "hanya membantu membaca" di sekitar alat | Hanya kolom isian, contoh, dan bagian-bagian alamat. Pesan hanya muncul bila masukan belum berupa alamat | Terlalu ramai. Alat kini hanya menunjukkan struktur alamat, dan penilaian status ada di halaman Cek |
| Menu aktif sidebar admin | Latar ungu primer | Nada latar yang lebih terang dengan huruf tebal | Selaras dengan gaya hover yang lebih disukai |
| Daftar admin | Semua baris ditampilkan sekaligus ("tanpa paginasi, data demo di bawah 50 laporan") | Laporan dan Indikator dibagi 10 baris per halaman | Data bertambah, dan daftar panjang sulit dipindai |
| Tata letak formulir Cek dan Lacak | Baris bersejajar ke bawah (`align-items-end`), sehingga kolom Jenis dan tombol bergeser saat pesan galat muncul | Sejajar dari atas. Pesan galat hanya di bawah kolom isian | Tata letak tidak boleh berubah saat galat |
| Petunjuk dan contoh isian | Tetap untuk URL walau jenis diganti | Mengikuti jenis yang dipilih (URL, email, nomor) di Cek dan di tiap baris indikator Lapor | Contoh yang salah membingungkan |
| Footer publik | Kalimat "LaPhish, proyek akhir Pemrograman Berbasis Web" dan tautan | Hanya tautan "Masuk admin" | Kalimat itu tidak menambah informasi bagi pengguna |
| Tanda baca | Em dash di footer dan judul tab, titik tengah di keterangan admin, tanda "–" untuk data kosong | Koma, kata, atau kosong. Judul tab memakai `\|` | Penanda yang terkesan buatan mesin |
| Ikon fungsional saja (pangkasan lanjutan) | Ikon masih ada di menu sidebar admin, kolom isian (cari, pengguna, kunci), tombol aksi admin (tambah, filter, edit, hapus, simpan, keluar), kotak pesan, tautan kembali, dan tombol Kirim | Dihapus. Tersisa ikon merek, tombol mata di login, dan tombol hapus baris di Lapor | Teks di sebelahnya sudah menyampaikan artinya. Ikon yang tersisa adalah tombol tanpa teks |
| Warna status Baru | Ungu primer `#653F99`, sama dengan tombol utama | Biru `#1D4ED8` | Lencana dan tombol sama-sama berwarna ungu, sehingga status bisa terbaca sebagai tombol yang bisa diklik |
| Lencana di judul detail laporan | Mewarisi jarak huruf negatif dari judul sehingga hurufnya berdempet | Jarak huruf normal di semua lencana | Lencana tetap terbaca di dalam judul |
| Lebar daftar Kanal (A10) | Kartu tabel dibatasi 760 px, sementara judul dan tombol "Tambah kanal" selebar halaman | Kartu selebar halaman seperti daftar admin lain. Kolom Laporan dan aksi berlebar tetap (9 dan 13 rem), juga di daftar Kategori | Di layar lebar tombol terpisah jauh dari tabel dan halaman berbeda dari yang lain. Tepi kanan kartu dan tombol kini sejajar di semua daftar |

Yang **tidak** berubah: palet warna kampus, pemetaan warna status (kecuali Baru yang kini biru), Plus Jakarta Sans, Bootstrap Icons, sapaan formal, struktur menu sidebar admin, dan logika aplikasi.

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
| A5 | Ubah kategori dan kanal laporan | Admin | FR-08 |
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
| P1 | Hero berisi judul "Baca alamatnya sebelum Anda klik." dan alat **bedah alamat**: pengunjung menempel tautan atau memilih contoh, lalu bagian-bagian alamat ditampilkan dan domain pemilik situs disorot. Alat ini berjalan sepenuhnya di peramban (`assets/js/bedah_alamat.js`), tidak memakai database, dan hanya menunjukkan struktur alamat, tidak menyatakan sebuah situs aman atau berbahaya. Kartunya sengaja ramping: hanya kolom isian, tiga contoh, dan bagian-bagian alamat, tanpa judul, kalimat pengantar, atau pesan penilaian. Satu-satunya teks tambahan muncul bila masukan belum berupa alamat. Di bawahnya tiga jalur (Cek, Lapor, Lacak) sebagai baris besar, lalu "Cara kerja" tiga langkah bernomor sedang berwarna gelap dengan garis tipis di atas. |
| P2 | Indikator tampil 1 baris, dengan tombol "+ Tambah indikator" sampai maksimal 5 baris (JavaScript sederhana). Identitas pelapor ditaruh paling bawah dan ditandai opsional. Deskripsi kategori muncul di bawah dropdown kategori. Tiga seksi (Detail kejadian, Indikator, Identitas pelapor) diberi nomor bulat ungu karena memang berurutan. Galat validasi tampil sebagai ringkasan di atas formulir. Contoh isian (placeholder) di tiap baris indikator mengikuti jenis yang dipilih baris itu (URL, email, atau nomor), termasuk baris yang baru ditambah. |
| P3 | Kode tiket ditampilkan besar dalam kotak bergaris putus-putus, disertai pesan untuk menyimpannya, tanpa ikon centang besar. Tidak ada cara memulihkan kode tiket yang hilang. |
| P4 | Dropdown jenis wajib dipilih. Kalimat petunjuk di atas formulir dan contoh isian mengikuti jenisnya (URL, email, atau nomor), baik saat pilihan diganti maupun saat halaman dimuat ulang setelah kirim. Baris formulir sejajar dari atas, jadi pesan galat di bawah kolom isian tidak menggeser kolom Jenis maupun tombol. Nilai yang dicari ditampilkan kembali dalam bentuk *defanged*; untuk URL berupa bedah alamat (subdomain, domain pemilik situs, port, jalur) di dalam panel putih pada kotak hasil. Empat varian hasil mengikuti tabel logika Cek di `02a_desain_database.md` bagian 7. Di bawah hasil ada legenda "Arti setiap hasil". |
| P5 | Baris formulir sejajar dari atas seperti P4. Menampilkan kategori, kanal, tanggal kejadian, status terakhir, dan riwayat status beserta catatan admin. Kode tiket tampil sebagai kotak bergaris putus-putus. Titik pada riwayat mengikuti warna status dan entri terbaru bertanda "terkini". Di bawahnya ada legenda "Arti status laporan". Blok "Lihat laporan saya" (tertutup secara bawaan) membuka kronologi dan indikator yang dikirim, dengan nilai indikator berbentuk defanged. Tidak menampilkan identitas pelapor. |

## 4. Catatan rancangan halaman admin

| Halaman | Keputusan |
|---|---|
| A1 | Pesan galat login sama untuk username maupun password yang salah, agar tidak membocorkan username yang terdaftar. Kolom kata sandi punya tombol mata untuk menampilkan atau menyembunyikan isinya (ikon dan label berganti, status `aria-pressed` ikut berubah). |
| A2 | Lima kartu (total dan empat status) yang bisa diklik untuk membuka A3 dengan filter status. Kartu status memakai lencana status yang sama dengan daftar laporan, lalu angka besar; kartu total hanya bertuliskan "Total laporan". Tanpa ikon dan tanpa garis atas berwarna. Tiga tabel angka: per kategori, per kanal, dan per bulan (enam bulan terakhir, berdasarkan tanggal laporan masuk). Jumlah terbanyak di tiap tabel diberi penanda kuning dan teks "(terbanyak)" untuk pembaca layar. Tanpa grafik. |
| A3 | Pencarian dan filter dalam satu baris. Diurutkan dari laporan terbaru. Dibagi 10 baris per halaman (parameter `halaman`), dengan navigasi Sebelumnya, nomor halaman, dan Berikutnya di bawah tabel. Keterangan di atas tabel menyebut rentang baris ("Laporan 11 sampai 20 dari 42"), dan jumlah halaman mengikuti hasil filter. Navigasi tidak tampil bila hanya satu halaman. Tidak ada tombol tambah, karena laporan dibuat lewat P2. |
| A4 | Dua kolom: kiri berisi data laporan dan daftar indikator (verifikasi cepat lewat dropdown), kanan berisi form ubah status dan riwayat. Tombol "Ubah kategori dan kanal" dan Hapus di kanan atas. |
| A5 | Hanya kategori dan kanal yang bisa diubah. Tanggal kejadian dan kronologi tampil sebagai teks hanya baca karena keterangan pelapor tidak boleh diubah admin. |
| A6 | Tombol Hapus nonaktif jika indikator masih terkait laporan. Kolom "Laporan" menampilkan jumlah laporan terkait. Dibagi 10 baris per halaman dengan navigasi yang sama seperti A3. Kolom "Nilai" punya lebar minimum agar tidak menyusut menjadi beberapa huruf per baris di layar sedang. |
| A7 | Satu form untuk dua mode. Mode edit mengunci jenis dan nilai. Mode tambah menampilkan pesan "sudah tercatat" jika nilainya sudah ada. |
| A8–A11 | Di wireframe, daftar dan form ditampilkan berdampingan agar ringkas, tetapi di aplikasi tetap halaman terpisah. Tombol Hapus tetap aktif, dan penolakan datang dari database (RESTRICT) sehingga aturan FR-13 bisa didemokan. |
| Semua | Setiap aksi simpan atau hapus menampilkan pesan hasil (berhasil atau galat) di bagian atas halaman. Hapus selalu lewat form POST dengan dialog konfirmasi. |

## 5. Versi Figma (bisa diedit)

File: **LaporPhish — Mockup UI** (nama lama aplikasi, belum diganti di Figma) di Drafts tim pribadi Figma  
Tautan: https://www.figma.com/design/jikPy6XFfwXOyj0iWMHWxF

Status per 2026-11-20: **sebagian**. Pembuatan terhenti karena batas panggilan Figma MCP paket Starter.

| Bagian | Status |
|---|---|
| Halaman: "Fondasi & Komponen", "Publik (P1–P5)", "Admin (A1–A11)" | Selesai |
| 23 variabel warna (koleksi "LaporPhish — Warna", nama lama), 14 text style Plus Jakarta Sans / JetBrains Mono, 2 efek bayangan | Selesai |
| 31 komponen ikon garis 24 px (`icon/...`) | Selesai |
| Komponen Button, Badge Status, Alert, Kartu Statistik | Selesai |
| Komponen Field, Hasil Cek, Navbar, Footer, Sidebar | Terhenti di tengah (perlu dicek dan dirapikan) |
| 16 layar P1–P5 dan A1–A11 | Belum dibuat |

Catatan 2026-11-21: file Figma mengikuti **rancangan awal** (navbar gelap, hijau `#16A34A`) dan belum diperbarui ke tampilan akhir pada bagian 0. Memperbaruinya hanya dilakukan jika diminta.

Rencana cek perubahan: setelah file lengkap, struktur dan teks setiap layar disimpan sebagai snapshot acuan (`docs/mockup/figma_snapshot_v1.json`), lalu dibandingkan dengan isi file Figma saat diminta.
