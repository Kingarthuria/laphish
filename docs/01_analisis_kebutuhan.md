# Tahap 1 — Planning & Requirement Analysis

Proyek: **LaPhish: Lapor dan Cek Phishing di Kampus**

## 1. Latar belakang masalah

Mahasiswa, dosen, dan staf kampus sering menerima pesan phishing lewat email, WhatsApp, SMS, dan media sosial. Bentuknya bermacam-macam: tautan login palsu, pengumuman beasiswa atau hadiah palsu, lowongan kerja palsu, sampai file APK yang menyamar sebagai undangan.

Cara penanganan yang berjalan saat ini (manual):
- Peringatan diteruskan dari grup ke grup chat. Pesannya cepat tenggelam dan tidak bisa dicari ulang.
- Tidak ada catatan terpusat, sehingga unit TI tidak tahu berapa kasus yang terjadi dan jenis apa yang paling sering muncul.
- Orang yang menerima tautan mencurigakan tidak punya tempat untuk mengecek apakah tautan itu sudah pernah dilaporkan.
- Pelapor tidak tahu apakah laporannya ditindaklanjuti.

Data pendukung:
Sepanjang 2025, Anti-Phishing Working Group (APWG) mencatat 3,8 juta serangan phishing di seluruh dunia, dan phishing melalui SMS terus meningkat karena dapat menghindari filter email (APWG, 2026). Di Indonesia, BSSN mencatat 47,2 juta anomali trafik terkait situs phishing sepanjang 2023, terbanyak kedua setelah Trojan RAT, dan memasukkan phishing ke dalam 10 besar insiden yang ditangani (BSSN, 2024). Salah satu modus yang menonjol adalah file APK berkedok undangan pernikahan yang disebarkan lewat WhatsApp (BSSN, 2024).

> Catatan: angka BSSN adalah jumlah deteksi anomali trafik, bukan jumlah korban atau serangan. Jangan diringkas menjadi "47 juta serangan phishing".

Referensi:
- Anti-Phishing Working Group. (2026). *Phishing activity trends report: 4th quarter 2025*. https://docs.apwg.org/reports/apwg_trends_report_q4_2025.pdf (hlm. 3–4)
- Badan Siber dan Sandi Negara. (2024). *Lanskap keamanan siber Indonesia 2023*. Id-SIRTII/CC – BSSN. https://www.bssn.go.id/monitoring-keamanan-siber/ (hlm. 15–16, 58, 104)

## 2. Tujuan

1. Menyediakan satu tempat untuk melaporkan insiden phishing di lingkungan kampus.
2. Membantu pengguna mengecek tautan, email, atau nomor mencurigakan sebelum menanggapinya.
3. Membantu unit TI mengelola, memverifikasi, dan memantau status penanganan laporan.
4. Menyediakan rekap kasus per kategori, kanal, dan periode sebagai bahan sosialisasi keamanan.

## 3. Pengguna (aktor)

| Aktor | Siapa | Akses |
|---|---|---|
| Pengunjung | Mahasiswa, dosen, staf (sivitas akademika) | Tanpa login. Bisa melapor, mengecek indikator, dan melacak status laporan dengan kode tiket. |
| Admin | Petugas unit TI kampus | Wajib login. Mengelola semua data dan memverifikasi laporan. |

## 4. Nilai manfaat

Perbandingan cara manual saat ini (peringatan lewat grup chat) dengan LaPhish:

| Aspek | Cara manual (grup chat) | Dengan LaPhish |
|---|---|---|
| Pencatatan | Peringatan tersebar di banyak grup dan cepat tenggelam | Semua laporan tersimpan di satu database |
| Mengecek tautan mencurigakan | Harus menggulir riwayat chat atau bertanya ke grup | Tempel tautan di halaman Cek, hasil langsung muncul |
| Laporan ganda | Tautan yang sama diteruskan berkali-kali tanpa diketahui sudah dilaporkan | Tautan yang sama tersimpan sekali dan terhubung ke semua laporannya |
| Kejelasan tindak lanjut | Pelapor tidak tahu apakah laporannya ditangani | Pelapor melacak status dan catatan admin dengan kode tiket |
| Rekap untuk sosialisasi | Unit TI harus menghitung manual, atau tidak ada data sama sekali | Dashboard menampilkan jumlah per status, kategori, kanal, dan bulan secara otomatis |
| Keamanan saat berbagi | Tautan berbahaya ikut diteruskan dalam bentuk yang bisa diklik | Tautan ditampilkan dalam bentuk *defanged* sehingga tidak bisa diklik |
| Privasi pelapor | Nama dan nomor pengirim peringatan terlihat oleh seluruh anggota grup | Identitas opsional dan tidak tampil di halaman publik |

## 5. Ruang lingkup

**Termasuk:**
- Pelaporan insiden beserta 1–5 indikator (URL, email, nomor telepon).
- Pengecekan indikator dengan pencocokan terhadap data indikator yang tercatat (dari laporan maupun yang ditambahkan admin).
- Pelacakan status laporan memakai kode tiket.
- Panel admin: CRUD laporan, indikator, kategori, dan kanal; perubahan status beserta riwayatnya; dashboard rekap.

**Tidak termasuk (bisa dikembangkan di fase lanjutan):**
- Akun untuk pengunjung.
- Halaman kelola akun admin. Akun admin dibuat lewat file `.sql`.
- Unggah tangkapan layar atau file bukti, karena menambah risiko keamanan unggah file.
- Grafik di dashboard. Fase ini memakai kartu dan tabel angka.
- Data peran pelapor (mahasiswa/dosen/staf) dan rekap per peran.
- Status lanjutan setelah Valid/Tidak valid (misalnya "Selesai").
- Deteksi otomatis atau model machine learning.
- Pemblokiran tautan, pengiriman notifikasi email/WhatsApp, dan deploy online.

## 6. Kebutuhan fungsional

### Pengunjung
| Kode | Kebutuhan | Keterangan |
|---|---|---|
| FR-01 | Melihat beranda | Berisi penjelasan singkat aplikasi dan tombol menuju Lapor, Cek, dan Lacak. |
| FR-02 | Mengirim laporan phishing | Isian: kanal, kategori, tanggal kejadian, kronologi, dan 1–5 indikator (jenis + nilai). Identitas pelapor (nama, email) boleh dikosongkan. |
| FR-03 | Menerima kode tiket | Setelah laporan terkirim, sistem menampilkan kode unik acak yang tidak bisa ditebak, misalnya `LP-7F3K9QX2`. |
| FR-04 | Melacak status laporan | Memasukkan kode tiket untuk melihat status terakhir dan riwayatnya. Identitas pelapor tidak ditampilkan. |
| FR-05 | Mengecek indikator | Memilih jenis (URL, Email, atau Nomor) lalu memasukkan nilainya. Nilai dinormalisasi sesuai jenisnya lalu dicocokkan dengan data indikator. Hasil ditentukan oleh status verifikasi indikator: **Terverifikasi phishing**, **Tercatat, masih ditinjau**, **Sudah diperiksa, tidak terbukti phishing**, atau **Belum ada data**. Tiga hasil terakhir disertai imbauan untuk tetap waspada. |

### Admin
| Kode | Kebutuhan | Keterangan |
|---|---|---|
| FR-06 | Login dan logout | Memakai username dan password. Password disimpan dalam bentuk hash. |
| FR-07 | Dashboard rekap | Kartu dan tabel angka: jumlah laporan per status, per kategori, per kanal, dan per bulan (berdasarkan tanggal laporan masuk). |
| FR-08 | Kelola laporan (CRUD) | **Create** dilakukan pengunjung lewat formulir lapor (FR-02). Admin melihat daftar dengan pencarian (kode tiket atau kata di kronologi) dan filter (status, kategori, rentang tanggal laporan masuk), melihat detail, mengubah kategori dan kanal, dan menghapus. Tanggal kejadian dan kronologi adalah keterangan pelapor, jadi admin tidak bisa mengubahnya (hanya bisa menghapus laporan). Indikator dikelola lewat FR-10. |
| FR-09 | Mengubah status laporan | Status: Baru, Ditinjau, Valid, Tidak valid. Alur umumnya Baru → Ditinjau → Valid / Tidak valid, tetapi admin bebas memilih status berikutnya. Setiap perubahan dicatat beserta catatan admin dan waktunya. Status laporan menggambarkan **penanganan kasus**. |
| FR-10 | Kelola indikator (CRUD) | Satu indikator bisa terkait dengan banyak laporan. Admin menetapkan status verifikasi (Belum diverifikasi, Phishing, Bukan phishing). Status verifikasi menentukan **hasil fitur Cek**. Admin juga bisa menambah indikator tanpa laporan, misalnya dari peringatan resmi, dengan mengisi catatan sumbernya. Verifikasi bisa dilakukan langsung dari halaman detail laporan. Yang bisa diubah hanya status verifikasi dan catatan; nilai indikator tidak bisa diubah. Jika indikator yang ditambahkan sudah tercatat, sistem menampilkan pesan dan tautan ke data yang sudah ada. Indikator yang masih terkait laporan tidak bisa dihapus karena menjadi bukti laporan, tetapi bisa ditandai Bukan phishing. |
| FR-11 | Kelola kategori (CRUD) | Contoh: pencurian akun, hadiah/beasiswa palsu, lowongan palsu, APK berbahaya. Deskripsi kategori tampil sebagai teks bantuan di formulir lapor. |
| FR-12 | Kelola kanal (CRUD) | Contoh: email, WhatsApp, SMS, media sosial, situs web. |
| FR-13 | Mencegah penghapusan data yang masih dipakai | Kategori, kanal, atau indikator yang masih terkait laporan tidak bisa dihapus. Sistem menampilkan pesan yang jelas. |

## 7. Kebutuhan non-fungsional

### Perangkat lunak
| Komponen | Spesifikasi |
|---|---|
| Sistem operasi | Windows 11 |
| Web server | XAMPP: Apache, PHP 8.2.12, MariaDB 10.4.32 |
| Pengelola database | phpMyAdmin (bawaan XAMPP) |
| Front-end | Bootstrap 5 |
| Browser | Google Chrome atau Microsoft Edge versi terbaru |
| Alat bantu | Visual Studio Code, Mermaid untuk ERD (draw.io opsional), wireframe HTML statis untuk mockup (`docs/mockup/`), Git dan GitHub |

### Perangkat keras (minimal)
| Komponen | Spesifikasi |
|---|---|
| Prosesor | Dual-core 2 GHz |
| RAM | 4 GB |
| Penyimpanan kosong | 2 GB untuk XAMPP dan proyek |

### Kualitas sistem
| Kode | Aspek | Kebutuhan |
|---|---|---|
| NFR-01 | Keamanan kueri | Semua kueri memakai PDO prepared statement. |
| NFR-02 | Keamanan tampilan | Semua output di-escape dengan `htmlspecialchars` untuk mencegah XSS. |
| NFR-03 | Keamanan indikator | URL berbahaya ditampilkan dalam bentuk *defanged* (`hxxp://contoh[.]com`), tidak pernah sebagai link yang bisa diklik. |
| NFR-04 | Autentikasi | Password memakai `password_hash`/`password_verify`. Halaman admin dilindungi session, dan ID session diperbarui saat login (`session_regenerate_id`). |
| NFR-05 | Konfigurasi | Kredensial database disimpan di file konfigurasi terpisah yang tidak di-commit. Repo hanya berisi contohnya. |
| NFR-06 | Penanganan galat | Galat database ditangkap dengan `try/catch`. Pengguna melihat pesan umum, bukan pesan teknis. |
| NFR-07 | Validasi input | Input divalidasi di sisi server (wajib isi, format email, format URL, panjang maksimum). |
| NFR-08 | Kemudahan pakai | Tampilan responsif (Bootstrap). Label dan pesan galat ditulis dalam Bahasa Indonesia. |
| NFR-09 | Privasi | Identitas pelapor bersifat opsional dan tidak tampil di halaman publik. Seluruh data contoh adalah dummy. |
| NFR-10 | Keamanan aksi hapus | Penghapusan data hanya lewat form POST dengan dialog konfirmasi, tidak lewat link GET. |

## 8. Asumsi dan batasan

- Aplikasi hanya berjalan di localhost untuk keperluan demo.
- Hanya ada satu peran admin, tanpa pembagian hak akses yang lebih rinci. Akun admin dibuat lewat file `.sql`.
- Pengecekan indikator memakai pencocokan persis setelah normalisasi (huruf kecil, tanpa `http://`/`https://`/`www.`, tanpa garis miring di akhir, nomor `08…` diubah menjadi `628…`). Variasi tautan yang sedikit berbeda tidak akan terdeteksi.
- Hasil "Belum ada data" dan "Tidak terbukti phishing" tidak berarti tautan itu pasti aman.
