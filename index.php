<?php
/**
 * P1: Beranda (FR-01)
 * Penjelasan singkat aplikasi, alat bedah alamat, dan jalan pintas ke Cek, Lapor, dan Lacak.
 * Alat bedah alamat berjalan sepenuhnya di peramban (assets/js/bedah_alamat.js) dan tidak memakai database.
 */
require __DIR__ . '/includes/init.php';

$pageTitle = 'Beranda';
$activeNav = 'beranda';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5">
    <div class="row g-4 g-lg-5 align-items-start mb-5">
        <div class="col-lg-5">
            <h1 class="lp-display mb-3">Baca alamatnya sebelum Anda klik.</h1>
            <p class="lead text-muted-lp">Tautan phishing sering meniru nama kampus. Periksa dulu, dan laporkan agar sivitas akademika lain tidak ikut tertipu.</p>
            <a class="btn btn-primary btn-lg me-2 mb-2" href="<?= url('cek.php') ?>">Cek tautan</a>
            <a class="btn btn-outline-primary btn-lg mb-2" href="<?= url('lapor.php') ?>">Buat laporan</a>
        </div>
        <div class="col-lg-7">
            <section class="lp-lens" aria-label="Bedah alamat tautan">
                <form id="bedahForm" class="d-flex flex-column flex-sm-row gap-2" autocomplete="off">
                    <label class="visually-hidden" for="bedahInput">Tautan yang ingin dibedah</label>
                    <input class="form-control lp-mono" id="bedahInput" type="text" spellcheck="false" value="https://login.kampus-sso.verifikasi-akun.top/masuk">
                    <button class="btn btn-primary" type="submit">Bedah</button>
                </form>
                <div class="d-flex flex-wrap gap-2 mt-3" role="group" aria-label="Contoh tautan">
                    <button class="lp-chip" type="button" data-url="https://login.kampus-sso.verifikasi-akun.top/masuk" aria-pressed="true">Meniru nama kampus</button>
                    <button class="lp-chip" type="button" data-url="https://contoh-kampus.ac.id.cek-nilai.top/" aria-pressed="false">Nama asli di depan</button>
                    <button class="lp-chip" type="button" data-url="https://sso.contoh-kampus.ac.id/masuk" aria-pressed="false">Alamat kampus</button>
                </div>
                <div class="lp-anatomy" aria-live="polite">
                    <div class="lp-url" id="bedahUrl"></div>
                    <p class="lp-vonis" id="bedahVonis"></p>
                    <noscript><p class="small text-muted-lp mb-0">Alat ini butuh JavaScript. Contoh: pada <span class="lp-mono">hxxps://login[.]kampus-sso[.]verifikasi-akun[.]top/masuk</span>, pemilik situsnya adalah <span class="lp-mono">verifikasi-akun[.]top</span>, bukan kampus.</p></noscript>
                </div>
            </section>
        </div>
    </div>

    <nav class="lp-paths mb-5" aria-label="Yang bisa Anda lakukan">
        <a class="lp-path" href="<?= url('cek.php') ?>">
            <span class="word">Cek</span>
            <span class="text-muted-lp">Periksa apakah tautan, email, atau nomor sudah pernah dilaporkan atau terverifikasi phishing.</span>
            <span class="go">Cek indikator</span>
        </a>
        <a class="lp-path" href="<?= url('lapor.php') ?>">
            <span class="word">Lapor</span>
            <span class="text-muted-lp">Laporkan tautan, email, atau nomor phishing yang Anda terima. Identitas boleh dikosongkan.</span>
            <span class="go">Buat laporan</span>
        </a>
        <a class="lp-path" href="<?= url('lacak.php') ?>">
            <span class="word">Lacak</span>
            <span class="text-muted-lp">Pantau tindak lanjut laporan Anda memakai kode tiket.</span>
            <span class="go">Lacak laporan</span>
        </a>
    </nav>

    <h2 class="h4 fw-bold mb-4">Cara kerja</h2>
    <ol class="lp-steps">
        <li>Kirim laporan beserta tautan, nomor, atau email yang mencurigakan.</li>
        <li>Unit TI memeriksa dan memverifikasi laporan.</li>
        <li>Hasil verifikasi bisa dicek siapa saja lewat halaman Cek.</li>
    </ol>
</div>
<script src="<?= url('assets/js/bedah_alamat.js') ?>"></script>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
