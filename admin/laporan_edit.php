<?php
/**
 * A5 — Ubah kategori dan kanal laporan (FR-08 Update)
 * Hanya kategori dan kanal yang bisa diubah (aturan integritas nomor 6 di docs/02a_desain_database.md).
 * Tanggal kejadian dan kronologi adalah keterangan pelapor: tidak ada kolom isiannya, dan nilai
 * kiriman untuk keduanya diabaikan di server walaupun ada permintaan POST buatan sendiri.
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

    if (!in_array($old['category_id'], array_map('intval', array_column($categories, 'category_id')), true)) {
        $errors[] = 'Pilih kategori.';
    }
    if (!in_array($old['channel_id'], array_map('intval', array_column($channels, 'channel_id')), true)) {
        $errors[] = 'Pilih kanal.';
    }

    if (!$errors) {
        try {
            $pdo->prepare('UPDATE reports SET category_id = ?, channel_id = ? WHERE report_id = ?')
                ->execute([$old['category_id'], $old['channel_id'], $id]);
            flash('sukses', 'Kategori dan kanal laporan ' . $report['ticket_code'] . ' berhasil disimpan.');
            redirect('admin/laporan_detail.php?id=' . $id);
        } catch (PDOException $e) {
            error_log('[LaPhish] Edit laporan gagal: ' . $e->getMessage());
            $errors[] = 'Perubahan gagal disimpan karena gangguan sistem.';
        }
    }
}

$pageTitle = 'Ubah kategori dan kanal ' . $report['ticket_code'];
$activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/laporan_detail.php?id=' . $id) ?>" class="text-decoration-none">Kembali ke detail laporan</a></div>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Ubah Kategori dan Kanal <span class="font-monospace"><?= e($report['ticket_code']) ?></span></h1></div>

<div class="card p-4" style="max-width: 800px">
    <?php if ($errors): ?>
        <div class="alert alert-err small" role="alert"><ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <div class="alert alert-info-lp small py-2">Petugas hanya mengoreksi kategori dan kanal. Tanggal kejadian dan kronologi adalah keterangan pelapor dan tidak bisa diubah. Status diubah di halaman detail, indikator di menu Indikator.</div>
    <form method="post" action="<?= url('admin/laporan_edit.php?id=' . $id) ?>" novalidate>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="category_id">Kategori <span class="req">*</span></label>
                <select class="form-select" id="category_id" name="category_id">
                    <?php foreach ($categories as $c): ?><option value="<?= (int) $c['category_id'] ?>"<?= (int) $old['category_id'] === (int) $c['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="channel_id">Kanal <span class="req">*</span></label>
                <select class="form-select" id="channel_id" name="channel_id">
                    <?php foreach ($channels as $c): ?><option value="<?= (int) $c['channel_id'] ?>"<?= (int) $old['channel_id'] === (int) $c['channel_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <div class="text-muted-lp small">Tanggal kejadian</div>
                <div><?= e(format_date($report['incident_date'])) ?></div>
            </div>
            <div class="col-12">
                <div class="text-muted-lp small">Kronologi (keterangan pelapor, hanya baca)</div>
                <div style="white-space: pre-line"><?= e($report['chronology']) ?></div>
            </div>
        </div>
        <div class="d-flex gap-2 justify-content-end mt-4">
            <a class="btn btn-outline-secondary" href="<?= url('admin/laporan_detail.php?id=' . $id) ?>">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan perubahan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
