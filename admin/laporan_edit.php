<?php
/**
 * A5 — Edit laporan (FR-08 Update)
 * Hanya kategori, kanal, tanggal kejadian, dan kronologi yang bisa diubah
 * (aturan integritas nomor 6 di docs/02a_desain_database.md).
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT report_id, ticket_code, category_id, channel_id, incident_date, chronology FROM reports WHERE report_id = ?');
$stmt->execute([$id]);
$report = $stmt->fetch();
if (!$report) {
    flash('galat', 'Laporan tidak ditemukan.');
    redirect('admin/laporan.php');
}

$categories = $pdo->query('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();
$channels = $pdo->query('SELECT channel_id, name FROM channels ORDER BY name')->fetchAll();
$errors = [];
$old = $report;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $old['channel_id'] = (int) ($_POST['channel_id'] ?? 0);
    $old['incident_date'] = trim((string) ($_POST['incident_date'] ?? ''));
    $old['chronology'] = trim((string) ($_POST['chronology'] ?? ''));

    if (!in_array($old['category_id'], array_map('intval', array_column($categories, 'category_id')), true)) {
        $errors[] = 'Pilih kategori.';
    }
    if (!in_array($old['channel_id'], array_map('intval', array_column($channels, 'channel_id')), true)) {
        $errors[] = 'Pilih kanal.';
    }
    $date = DateTime::createFromFormat('!Y-m-d', $old['incident_date']);
    if (!$date || $date->format('Y-m-d') !== $old['incident_date']) {
        $errors[] = 'Tanggal kejadian wajib diisi dengan format yang benar.';
    } elseif ($date > new DateTime('today')) {
        $errors[] = 'Tanggal kejadian tidak boleh di masa depan.';
    }
    $len = mb_strlen($old['chronology']);
    if ($len < 10 || $len > 3000) {
        $errors[] = 'Kronologi wajib diisi, 10 sampai 3.000 karakter.';
    }

    if (!$errors) {
        try {
            $pdo->prepare('UPDATE reports SET category_id = ?, channel_id = ?, incident_date = ?, chronology = ? WHERE report_id = ?')
                ->execute([$old['category_id'], $old['channel_id'], $old['incident_date'], $old['chronology'], $id]);
            flash('sukses', 'Perubahan laporan ' . $report['ticket_code'] . ' berhasil disimpan.');
            redirect('admin/laporan_detail.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('[LaporPhish] Edit laporan gagal: ' . $e->getMessage());
            $errors[] = 'Perubahan gagal disimpan karena gangguan sistem.';
        }
    }
}

$pageTitle = 'Edit laporan ' . $report['ticket_code'];
$activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/laporan_detail.php?id=' . $id) ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke detail laporan</a></div>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Edit Laporan <span class="font-monospace"><?= e($report['ticket_code']) ?></span></h1></div>

<div class="card p-4" style="max-width: 800px">
    <?php if ($errors): ?>
        <div class="alert alert-err small" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <div class="alert alert-info-lp small py-2"><i class="bi bi-info-circle me-1"></i>Hanya empat kolom ini yang bisa diubah. Status diubah di halaman detail, indikator di menu Indikator.</div>
    <form method="post" action="<?= url('admin/laporan_edit.php?id=' . $id) ?>" novalidate>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="category_id">Kategori <span class="req">*</span></label>
                <select class="form-select" id="category_id" name="category_id">
                    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['category_id'] ?>"<?= (int) $old['category_id'] === (int) $c['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="channel_id">Kanal <span class="req">*</span></label>
                <select class="form-select" id="channel_id" name="channel_id">
                    <?php foreach ($channels as $c): ?><option value="<?= (int) $c['channel_id'] ?>"<?= (int) $old['channel_id'] === (int) $c['channel_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="incident_date">Tanggal kejadian <span class="req">*</span></label>
                <input type="date" class="form-control" id="incident_date" name="incident_date" max="<?= date('Y-m-d') ?>" value="<?= e((string) $old['incident_date']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="chronology">Kronologi <span class="req">*</span></label>
                <textarea class="form-control" id="chronology" name="chronology" rows="4" maxlength="3000"><?= e($old['chronology']) ?></textarea>
            </div>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-4">
            <a class="btn btn-outline-secondary" href="<?= url('admin/laporan_detail.php?id=' . $id) ?>">Batal</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Simpan perubahan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
