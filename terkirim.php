<?php
/**
 * P3: Laporan terkirim (FR-03)
 * Menampilkan kode tiket laporan terakhir yang dikirim dari browser ini.
 */
require __DIR__ . '/includes/init.php';

$ticket = $_SESSION['last_ticket'] ?? null;
if (!$ticket) {
    redirect('lapor.php');
}

$pageTitle = 'Laporan terkirim';
$activeNav = 'lapor';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5 text-center">
    <div class="card mx-auto p-4 p-md-5" style="max-width: 620px">
        <h1 class="h3 fw-bold">Laporan berhasil dikirim</h1>
        <p class="text-muted-lp">Terima kasih. Simpan kode tiket berikut untuk melacak tindak lanjut laporan Anda.</p>
        <div><span class="lp-ticket"><?= e($ticket) ?></span></div>
        <div class="alert alert-warn text-start small mt-4 mb-4">
            <strong>Penting:</strong> kode tiket tidak bisa dipulihkan jika hilang. Salin atau screenshot halaman ini.
        </div>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <form method="post" action="<?= url('lacak.php') ?>">
                <input type="hidden" name="ticket" value="<?= e($ticket) ?>">
                <button class="btn btn-primary" type="submit">Lacak laporan ini</button>
            </form>
            <a class="btn btn-outline-primary" href="<?= url('index.php') ?>">Kembali ke beranda</a>
        </div>
    </div>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
