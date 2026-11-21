<?php
/**
 * A10 — Daftar kanal (FR-12 Read/Delete, FR-13)
 * Pola sama dengan kategori: penghapusan kanal yang masih dipakai ditolak database (FK RESTRICT).
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = $pdo->prepare('SELECT name FROM channels WHERE channel_id = ?');
    $name->execute([$id]);
    $name = (string) $name->fetchColumn();
    try {
        $stmt = $pdo->prepare('DELETE FROM channels WHERE channel_id = ?');
        $stmt->execute([$id]);
        flash($stmt->rowCount() ? 'sukses' : 'galat', $stmt->rowCount() ? 'Kanal "' . $name . '" berhasil dihapus.' : 'Kanal tidak ditemukan.');
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1451) {
            $n = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE channel_id = ?');
            $n->execute([$id]);
            flash('galat', 'Kanal "' . $name . '" tidak bisa dihapus karena masih dipakai ' . (int) $n->fetchColumn() . ' laporan.');
        } else {
            error_log('[LaPhish] Hapus kanal gagal: ' . $e->getMessage());
            flash('galat', 'Kanal gagal dihapus karena gangguan sistem.');
        }
    }
    redirect('admin/kanal.php');
}

$rows = $pdo->query(
    'SELECT ch.channel_id, ch.name, COUNT(r.report_id) AS report_count
     FROM channels ch LEFT JOIN reports r ON r.channel_id = ch.channel_id
     GROUP BY ch.channel_id, ch.name ORDER BY ch.name'
)->fetchAll();

$pageTitle = 'Kanal';
$activeMenu = 'kanal';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Kanal</h1><a class="btn btn-primary" href="<?= url('admin/kanal_form.php') ?>"><i class="bi bi-plus-lg me-1"></i>Tambah kanal</a></div>
<div class="card" style="max-width: 760px">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th class="ps-3">Nama</th><th class="text-center">Laporan</th><th class="pe-3"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?= e($r['name']) ?></td>
                    <td class="text-center"><?= (int) $r['report_count'] ?></td>
                    <td class="pe-3 text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/kanal_form.php?id=' . (int) $r['channel_id']) ?>">Edit</a>
                        <form method="post" action="<?= url('admin/kanal.php') ?>" class="d-inline" data-confirm="Hapus kanal &quot;<?= e($r['name']) ?>&quot;?">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $r['channel_id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
