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
    <p class="text-muted-lp">Masukkan kode tiket yang Anda dapat setelah mengirim laporan.</p>

    <div class="card p-4 mb-4">
        <form method="post" action="<?= url('lacak.php') ?>" novalidate>
            <div class="row g-2 align-items-start">
                <div class="col-md">
                    <label class="form-label" for="ticket">Kode tiket <span class="req">*</span></label>
                    <input class="form-control font-monospace<?= $error ? ' is-invalid' : '' ?>" id="ticket" name="ticket" maxlength="11" value="<?= e($ticket) ?>" placeholder="LP-XXXXXXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="ticketHint" required>
                    <?php if ($error): ?><div class="invalid-feedback"><?= e($error) ?></div><?php endif; ?>
                    <div class="form-text" id="ticketHint">Bentuknya LP- diikuti 8 huruf atau angka.</div>
                </div>
                <div class="col-md-auto"><span class="form-label d-none d-md-block invisible" aria-hidden="true">&nbsp;</span><button class="btn btn-primary w-100" type="submit">Lacak</button></div>
            </div>
        </form>
    </div>

    <?php if ($report): ?>
        <div class="card p-4 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3" role="status">
                <div><div class="small text-muted-lp mb-1">Kode tiket</div><span class="lp-ticket lp-ticket-sm"><?= e($report['ticket_code']) ?></span></div>
                <div class="text-sm-end"><div class="small text-muted-lp mb-1">Status saat ini</div><?= status_badge($report['status']) ?></div>
            </div>
            <div class="row g-3">
                <div class="col-sm-4"><div class="small text-muted-lp">Kategori</div><div class="fw-semibold"><?= e($report['category']) ?></div></div>
                <div class="col-sm-4"><div class="small text-muted-lp">Kanal</div><div class="fw-semibold"><?= e($report['channel']) ?></div></div>
                <div class="col-sm-4"><div class="small text-muted-lp">Tanggal kejadian</div><div class="fw-semibold"><?= e(format_date($report['incident_date'])) ?></div></div>
            </div>
        </div>

        <div class="card p-4">
            <h2 class="lp-sec mb-3">Riwayat penanganan</h2>
            <div class="timeline">
                <?php foreach ($history as $i => $item): ?>
                    <div class="item item-<?= e($item['status']) ?>">
                        <?= status_badge($item['status']) ?>
                        <span class="small text-muted-lp ms-1"><?= e(format_datetime($item['changed_at'])) ?></span>
                        <?php if ($i === 0): ?><span class="lp-tag ms-1">terkini</span><?php endif; ?>
                        <div class="mt-1<?= $item['note'] ? '' : ' text-muted-lp' ?>"><?= $item['note'] ? e($item['note']) : ($item['status'] === 'baru' ? 'Laporan diterima.' : 'Tidak ada catatan.') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <h2 class="h5 fw-bold mt-5 mb-3">Arti status laporan</h2>
    <?php
    $arti = [
        'baru'        => 'Laporan sudah diterima dan menunggu ditinjau unit TI.',
        'ditinjau'    => 'Unit TI sedang memeriksa laporan Anda.',
        'valid'       => 'Laporan terbukti berisi phishing.',
        'tidak_valid' => 'Laporan tidak terbukti berisi phishing.',
    ];
    ?>
    <ul class="lp-keys">
        <?php foreach ($arti as $key => $teks): ?>
            <li><?= status_badge($key) ?><span class="text-muted-lp"><?= e($teks) ?></span></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
