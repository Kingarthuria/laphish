<?php
/**
 * A4 — Detail laporan (FR-08 Read/Delete, FR-09, FR-10)
 * POST action=status : menambah baris riwayat status (tidak pernah mengubah riwayat lama).
 * POST action=verify : verifikasi cepat indikator milik laporan ini.
 * POST action=delete : menghapus laporan; penghubung dan riwayat ikut terhapus (CASCADE).
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT r.*, c.name AS category, ch.name AS channel, rp.name AS reporter_name, rp.email AS reporter_email, s.status
     FROM reports r
     JOIN categories c ON c.category_id = r.category_id
     JOIN channels ch ON ch.channel_id = r.channel_id
     LEFT JOIN reporters rp ON rp.reporter_id = r.reporter_id
     JOIN v_report_status s ON s.report_id = r.report_id
     WHERE r.report_id = ?'
);
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) {
    flash('galat', 'Laporan tidak ditemukan.');
    redirect('admin/laporan.php');
}

$self = 'admin/laporan_detail.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $admin = current_admin()['id'];
    try {
        if ($action === 'status') {
            $newStatus = (string) ($_POST['status'] ?? '');
            $note = trim((string) ($_POST['note'] ?? ''));
            if (!isset(REPORT_STATUSES[$newStatus])) {
                flash('galat', 'Pilih status yang tersedia.');
            } elseif (mb_strlen($note) > 1000) {
                flash('galat', 'Catatan terlalu panjang (maksimal 1.000 karakter).');
            } else {
                $pdo->prepare('INSERT INTO status_histories (report_id, status, note, admin_id) VALUES (?, ?, ?, ?)')
                    ->execute([$id, $newStatus, $note !== '' ? $note : null, $admin]);
                flash('sukses', 'Status berhasil diubah menjadi "' . REPORT_STATUSES[$newStatus]['label'] . '".');
            }
        } elseif ($action === 'verify') {
            $indicatorId = (int) ($_POST['indicator_id'] ?? 0);
            $vs = (string) ($_POST['verification_status'] ?? '');
            $owned = $pdo->prepare('SELECT 1 FROM report_indicators WHERE report_id = ? AND indicator_id = ?');
            $owned->execute([$id, $indicatorId]);
            if (!isset(VERIFICATION_STATUSES[$vs]) || !$owned->fetchColumn()) {
                flash('galat', 'Verifikasi tidak valid.');
            } else {
                $sql = $vs === 'belum'
                    ? 'UPDATE indicators SET verification_status = ?, verified_by = NULL, verified_at = NULL WHERE indicator_id = ?'
                    : 'UPDATE indicators SET verification_status = ?, verified_by = ' . (int) $admin . ', verified_at = NOW() WHERE indicator_id = ?';
                $pdo->prepare($sql)->execute([$vs, $indicatorId]);
                flash('sukses', 'Status verifikasi indikator diperbarui menjadi "' . VERIFICATION_STATUSES[$vs]['label'] . '".');
            }
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM reports WHERE report_id = ?')->execute([$id]);
            flash('sukses', 'Laporan ' . $report['ticket_code'] . ' berhasil dihapus beserta riwayatnya. Indikatornya tetap tersimpan.');
            redirect('admin/laporan.php');
        }
    } catch (PDOException $e) {
        error_log('[LaPhish] Aksi detail laporan gagal: ' . $e->getMessage());
        flash('galat', 'Perubahan gagal disimpan karena gangguan sistem.');
    }
    redirect($self);
}

$ind = $pdo->prepare(
    'SELECT i.indicator_id, i.type, i.value, i.verification_status, ri.raw_value
     FROM report_indicators ri JOIN indicators i ON i.indicator_id = ri.indicator_id
     WHERE ri.report_id = ? ORDER BY i.indicator_id'
);
$ind->execute([$id]);
$indicators = $ind->fetchAll();

$hist = $pdo->prepare(
    'SELECT h.status, h.note, h.changed_at, a.full_name
     FROM status_histories h LEFT JOIN admins a ON a.admin_id = h.admin_id
     WHERE h.report_id = ? ORDER BY h.history_id DESC'
);
$hist->execute([$id]);
$history = $hist->fetchAll();

$pageTitle = 'Laporan ' . $report['ticket_code'];
$activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/laporan.php') ?>" class="text-decoration-none">Kembali ke daftar laporan</a></div>
<div class="page-head">
    <h1 class="h4 fw-bold mb-0">Laporan <span class="font-monospace"><?= e($report['ticket_code']) ?></span> <span class="align-middle ms-1"><?= status_badge($report['status']) ?></span></h1>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= url('admin/laporan_edit.php?id=' . $id) ?>">Ubah kategori dan kanal</a>
        <form method="post" action="<?= url($self) ?>" data-confirm="Hapus laporan <?= e($report['ticket_code']) ?>? Riwayat status ikut terhapus. Indikator tetap tersimpan.">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-outline-danger" type="submit">Hapus</button>
        </form>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card p-3 mb-3">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted-lp" style="width: 150px">Tanggal masuk</td><td><?= e(format_datetime($report['created_at'])) ?></td></tr>
                <tr><td class="text-muted-lp">Terakhir diedit</td><td><?= $report['updated_at'] ? e(format_datetime($report['updated_at'])) : '<span class="text-muted-lp">Belum pernah diedit</span>' ?></td></tr>
                <tr><td class="text-muted-lp">Tanggal kejadian</td><td><?= e(format_date($report['incident_date'])) ?></td></tr>
                <tr><td class="text-muted-lp">Kategori</td><td><?= e($report['category']) ?></td></tr>
                <tr><td class="text-muted-lp">Kanal</td><td><?= e($report['channel']) ?></td></tr>
                <tr><td class="text-muted-lp">Kronologi</td><td style="white-space: pre-line"><?= e($report['chronology']) ?></td></tr>
                <tr><td class="text-muted-lp">Pelapor</td><td>
                    <?php if ($report['reporter_id']): ?>
                        <?= e($report['reporter_name'] ?: '(tanpa nama)') ?><?php if ($report['reporter_email']): ?>, <span class="text-muted-lp"><?= e($report['reporter_email']) ?></span><?php endif; ?>
                    <?php else: ?><span class="text-muted-lp">Anonim</span><?php endif; ?>
                </td></tr>
            </table>
        </div>

        <div class="card">
            <div class="p-3 pb-2 fw-bold">Indikator (<?= count($indicators) ?>)</div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th class="ps-3">Jenis</th><th>Nilai</th><th class="pe-3" style="width: 250px">Verifikasi cepat</th></tr></thead>
                    <tbody>
                    <?php foreach ($indicators as $i): ?>
                        <tr>
                            <td class="ps-3"><?= e(INDICATOR_TYPES[$i['type']]) ?></td>
                            <td class="lp-defang small"><?= e(defang($i['value'])) ?></td>
                            <td class="pe-3">
                                <form method="post" action="<?= url($self) ?>" class="input-group input-group-sm">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="indicator_id" value="<?= (int) $i['indicator_id'] ?>">
                                    <select class="form-select" name="verification_status" aria-label="Status verifikasi">
                                        <?php foreach (VERIFICATION_STATUSES as $key => $v): ?><option value="<?= $key ?>"<?= $i['verification_status'] === $key ? ' selected' : '' ?>><?= e($v['label']) ?></option><?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-3 pb-3 small text-muted-lp">Catatan verifikasi bisa diisi lewat menu <a href="<?= url('admin/indikator.php') ?>">Indikator</a>.</div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card p-3 mb-3">
            <div class="fw-bold mb-2">Ubah status</div>
            <form method="post" action="<?= url($self) ?>">
                <input type="hidden" name="action" value="status">
                <label class="form-label small" for="status">Status baru <span class="req">*</span></label>
                <select class="form-select mb-2" id="status" name="status">
                    <?php foreach (REPORT_STATUSES as $key => $s): ?><option value="<?= $key ?>"<?= $report['status'] === $key ? ' selected' : '' ?>><?= e($s['label']) ?></option><?php endforeach; ?>
                </select>
                <label class="form-label small" for="note">Catatan</label>
                <textarea class="form-control mb-1" id="note" name="note" rows="3" maxlength="1000" placeholder="Catatan ini tampil di halaman Lacak pelapor."></textarea>
                <div class="form-text mb-3">Tampil ke pelapor. Jangan menulis informasi internal.</div>
                <button class="btn btn-primary w-100" type="submit">Simpan status</button>
            </form>
        </div>
        <div class="card p-3">
            <div class="fw-bold mb-3">Riwayat status</div>
            <div class="timeline">
                <?php foreach ($history as $h): ?>
                    <div class="item">
                        <?= status_badge($h['status']) ?>
                        <span class="small text-muted-lp ms-1"><?= e(format_datetime($h['changed_at'])) ?>, oleh <?= e($h['full_name'] ?? 'Sistem') ?></span>
                        <?php if ($h['note']): ?><div class="small mt-1"><?= e($h['note']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
