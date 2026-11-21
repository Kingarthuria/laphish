<?php
/**
 * A6 — Daftar indikator (FR-10 Read/Delete, FR-13)
 * Indikator yang masih menjadi bukti laporan tidak bisa dihapus (FK RESTRICT);
 * tombol hapusnya dinonaktifkan dan database tetap menolak jika dicoba lewat cara lain.
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare('DELETE FROM indicators WHERE indicator_id = ?');
        $stmt->execute([$id]);
        flash($stmt->rowCount() ? 'sukses' : 'galat', $stmt->rowCount() ? 'Indikator berhasil dihapus.' : 'Indikator tidak ditemukan.');
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1451) {
            $n = $pdo->prepare('SELECT COUNT(*) FROM report_indicators WHERE indicator_id = ?');
            $n->execute([$id]);
            flash('galat', 'Indikator tidak bisa dihapus karena masih menjadi bukti di ' . (int) $n->fetchColumn() . ' laporan. Tandai sebagai "Bukan phishing" jika ternyata aman.');
        } else {
            error_log('[LaPhish] Hapus indikator gagal: ' . $e->getMessage());
            flash('galat', 'Indikator gagal dihapus karena gangguan sistem.');
        }
    }
    redirect('admin/indikator.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$type = (string) ($_GET['type'] ?? '');
$vs = (string) ($_GET['vs'] ?? '');

$where = [];
$params = [];
if ($q !== '') {
    $where[] = 'i.value LIKE ?';
    $params[] = '%' . $q . '%';
}
if (isset(INDICATOR_TYPES[$type])) {
    $where[] = 'i.type = ?';
    $params[] = $type;
} else {
    $type = '';
}
if (isset(VERIFICATION_STATUSES[$vs])) {
    $where[] = 'i.verification_status = ?';
    $params[] = $vs;
} else {
    $vs = '';
}
$stmt = $pdo->prepare(
    'SELECT i.indicator_id, i.type, i.value, i.verification_status, i.note,
            (SELECT COUNT(*) FROM report_indicators ri WHERE ri.indicator_id = i.indicator_id) AS report_count
     FROM indicators i'
    . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
    . ' ORDER BY i.created_at DESC, i.indicator_id DESC'
);
$stmt->execute($params);
$indicators = $stmt->fetchAll();

$pageTitle = 'Indikator';
$activeMenu = 'indikator';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Indikator</h1><a class="btn btn-primary" href="<?= url('admin/indikator_form.php') ?>"><i class="bi bi-plus-lg me-1"></i>Tambah indikator</a></div>

<form class="card p-3 mb-3" method="get" action="<?= url('admin/indikator.php') ?>">
    <div class="row g-2 align-items-end">
        <div class="col-lg-6"><label class="form-label small mb-1" for="q">Cari</label><div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Nilai indikator"></div></div>
        <div class="col-6 col-lg-2"><label class="form-label small mb-1" for="type">Jenis</label><select class="form-select" id="type" name="type"><option value="">Semua</option><?php foreach (INDICATOR_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= $type === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select></div>
        <div class="col-6 col-lg-2"><label class="form-label small mb-1" for="vs">Verifikasi</label><select class="form-select" id="vs" name="vs"><option value="">Semua</option><?php foreach (VERIFICATION_STATUSES as $k => $v): ?><option value="<?= $k ?>"<?= $vs === $k ? ' selected' : '' ?>><?= e($v['label']) ?></option><?php endforeach; ?></select></div>
        <div class="col-lg-2"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button></div>
    </div>
</form>

<div class="small text-muted-lp mb-2"><?= count($indicators) ?> indikator</div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th class="ps-3">Jenis</th><th>Nilai</th><th>Verifikasi</th><th class="text-center">Laporan</th><th>Catatan</th><th class="pe-3"></th></tr></thead>
            <tbody>
            <?php if (!$indicators): ?><tr><td colspan="6" class="text-center text-muted-lp py-4">Tidak ada indikator yang cocok.</td></tr><?php endif; ?>
            <?php foreach ($indicators as $i): $used = (int) $i['report_count'] > 0; ?>
                <tr>
                    <td class="ps-3"><?= e(INDICATOR_TYPES[$i['type']]) ?></td>
                    <td class="lp-defang small"><?= e(defang($i['value'])) ?></td>
                    <td><?= verification_badge($i['verification_status']) ?></td>
                    <td class="text-center"><?= (int) $i['report_count'] ?></td>
                    <td class="small<?= $i['note'] ? '' : ' text-muted-lp' ?>"><?= $i['note'] ? e($i['note']) : '–' ?></td>
                    <td class="pe-3 text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/indikator_form.php?id=' . (int) $i['indicator_id']) ?>">Edit</a>
                        <form method="post" action="<?= url('admin/indikator.php') ?>" class="d-inline" data-confirm="Hapus indikator ini? Tindakan ini tidak bisa dibatalkan.">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $i['indicator_id'] ?>">
                            <button class="btn btn-sm <?= $used ? 'btn-outline-secondary' : 'btn-outline-danger' ?>" type="submit"<?= $used ? ' disabled title="Masih terkait laporan"' : '' ?>>Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
