<?php
/**
 * P5 — Lacak status (FR-04)
 * Mencari laporan berdasarkan kode tiket, lalu menampilkan status terakhir dan riwayatnya.
 * Identitas pelapor dan indikator sengaja tidak ditampilkan.
 * Memakai POST agar kode tiket tidak tersimpan di alamat URL atau riwayat browser.
 */
require __DIR__ . '/includes/init.php';

$ticket = '';
$error = null;
$report = null;
$history = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket = strtoupper(trim((string) ($_POST['ticket'] ?? '')));

    if (!preg_match('/^LP-[A-Z0-9]{8}$/', $ticket)) {
        $error = 'Format kode tiket tidak sesuai. Contoh: LP-7F3K9QX2';
    } else {
        $stmt = db()->prepare(
            'SELECT r.report_id, r.ticket_code, r.incident_date, c.name AS category, ch.name AS channel, s.status
             FROM reports r
             JOIN categories c ON c.category_id = r.category_id
             JOIN channels ch ON ch.channel_id = r.channel_id
             JOIN v_report_status s ON s.report_id = r.report_id
             WHERE r.ticket_code = ?'
        );
        $stmt->execute([$ticket]);
        $report = $stmt->fetch() ?: null;

        if ($report) {
            $h = db()->prepare('SELECT status, note, changed_at FROM status_histories WHERE report_id = ? ORDER BY history_id DESC');
            $h->execute([$report['report_id']]);
            $history = $h->fetchAll();
        } else {
            $error = 'Kode tiket tidak ditemukan. Periksa kembali penulisannya.';
        }
    }
}

$pageTitle = 'Lacak laporan';
$activeNav = 'lacak';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5" style="max-width: 800px">
    <h1 class="h3 fw-bold">Lacak Laporan</h1>

    <div class="card p-4 mb-4">
        <form method="post" action="<?= url('lacak.php') ?>" novalidate>
            <div class="row g-2 align-items-end">
                <div class="col-md">
                    <label class="form-label" for="ticket">Kode tiket <span class="req">*</span></label>
                    <input class="form-control font-monospace<?= $error ? ' is-invalid' : '' ?>" id="ticket" name="ticket" maxlength="11" value="<?= e($ticket) ?>" placeholder="LP-XXXXXXXX" required>
                    <?php if ($error): ?><div class="invalid-feedback"><?= e($error) ?></div><?php endif; ?>
                </div>
                <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Lacak</button></div>
            </div>
        </form>
    </div>

    <?php if ($report): ?>
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                <div><div class="small text-muted-lp">Kode tiket</div><div class="fw-bold font-monospace fs-5"><?= e($report['ticket_code']) ?></div></div>
                <?= status_badge($report['status']) ?>
            </div>
            <div class="row g-3 small">
                <div class="col-sm-4"><div class="text-muted-lp">Kategori</div><div class="fw-semibold"><?= e($report['category']) ?></div></div>
                <div class="col-sm-4"><div class="text-muted-lp">Kanal</div><div class="fw-semibold"><?= e($report['channel']) ?></div></div>
                <div class="col-sm-4"><div class="text-muted-lp">Tanggal kejadian</div><div class="fw-semibold"><?= e(format_date($report['incident_date'])) ?></div></div>
            </div>
        </div>

        <div class="card p-4">
            <h2 class="h6 fw-bold mb-3">Riwayat penanganan</h2>
            <div class="timeline">
                <?php foreach ($history as $item): ?>
                    <div class="item">
                        <?= status_badge($item['status']) ?>
                        <span class="small text-muted-lp ms-1"><?= e(format_datetime($item['changed_at'])) ?></span>
                        <div class="mt-1<?= $item['note'] ? '' : ' text-muted-lp' ?>"><?= $item['note'] ? e($item['note']) : ($item['status'] === 'baru' ? 'Laporan diterima.' : '–') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
