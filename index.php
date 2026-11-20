<?php
/**
 * P1 — Beranda (FR-01)
 * Penjelasan singkat aplikasi dan jalan pintas ke Lapor, Cek, dan Lacak.
 */
require __DIR__ . '/includes/init.php';

$pageTitle = 'Beranda';
$activeNav = 'beranda';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5">
    <div class="row align-items-center g-4 mb-5">
        <div class="col-lg-7">
            <p class="text-uppercase fw-semibold small mb-2" style="color: var(--lp-primary)">Layanan pelaporan phishing kampus</p>
            <h1 class="display-6 fw-bold mb-3">Terima pesan atau tautan <span class="lp-underline">mencurigakan</span>?</h1>
            <p class="lead text-muted-lp">Periksa dulu sebelum diklik. Laporkan agar sivitas akademika lain tidak ikut tertipu.</p>
            <a class="btn btn-primary btn-lg me-2 mb-2" href="<?= url('cek.php') ?>"><i class="bi bi-search me-1"></i>Cek tautan sekarang</a>
            <a class="btn btn-outline-primary btn-lg mb-2" href="<?= url('lapor.php') ?>"><i class="bi bi-flag me-1"></i>Buat laporan</a>
        </div>
        <div class="col-lg-5">
            <div class="card p-4">
                <div class="small fw-semibold text-muted-lp mb-2">Contoh hasil cek</div>
                <div class="res res-red">
                    <div class="t fw-bold"><i class="bi bi-x-octagon-fill me-1"></i>Terverifikasi phishing</div>
                    <div class="lp-defang small mt-1"><?= e(defang('sso-kampus-verify.example/login')) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card h-100 p-4 lp-feature">
                <i class="bi bi-flag-fill align-self-start mb-3"></i>
                <h2 class="h5 fw-bold">Lapor</h2>
                <p class="text-muted-lp">Laporkan tautan, email, atau nomor phishing yang Anda terima. Identitas boleh dikosongkan.</p>
                <a href="<?= url('lapor.php') ?>" class="mt-auto fw-semibold text-decoration-none">Buat laporan <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 lp-feature">
                <i class="bi bi-search align-self-start mb-3"></i>
                <h2 class="h5 fw-bold">Cek</h2>
                <p class="text-muted-lp">Periksa apakah tautan, email, atau nomor sudah pernah dilaporkan atau terverifikasi phishing.</p>
                <a href="<?= url('cek.php') ?>" class="mt-auto fw-semibold text-decoration-none">Cek indikator <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-4 lp-feature">
                <i class="bi bi-ticket-detailed align-self-start mb-3"></i>
                <h2 class="h5 fw-bold">Lacak</h2>
                <p class="text-muted-lp">Pantau tindak lanjut laporan Anda memakai kode tiket.</p>
                <a href="<?= url('lacak.php') ?>" class="mt-auto fw-semibold text-decoration-none">Lacak laporan <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <h2 class="h5 fw-bold mb-3">Cara kerja</h2>
    <div class="row g-3">
        <div class="col-md-4"><div class="d-flex gap-3"><span class="lp-step">1</span><p class="mb-0">Kirim laporan beserta tautan, nomor, atau email yang mencurigakan.</p></div></div>
        <div class="col-md-4"><div class="d-flex gap-3"><span class="lp-step">2</span><p class="mb-0">Unit TI memeriksa dan memverifikasi laporan.</p></div></div>
        <div class="col-md-4"><div class="d-flex gap-3"><span class="lp-step">3</span><p class="mb-0">Hasil verifikasi bisa dicek siapa saja lewat halaman Cek.</p></div></div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
