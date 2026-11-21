<?php
/**
 * A8 — Daftar kategori (FR-11 Read/Delete, FR-13)
 * Tombol hapus sengaja tetap aktif: kategori yang masih dipakai ditolak oleh database (FK RESTRICT),
 * lalu aplikasi menampilkan pesan yang jelas.
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int) ($_POST['id'] ?? 0);
    $name = $pdo->prepare('SELECT name FROM categories WHERE category_id = ?');
    $name->execute([$id]);
    $name = (string) $name->fetchColumn();
    try {
        $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
        $stmt->execute([$id]);
        flash($stmt->rowCount() ? 'sukses' : 'galat', $stmt->rowCount() ? 'Kategori "' . $name . '" berhasil dihapus.' : 'Kategori tidak ditemukan.');
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1451) {
            $n = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE category_id = ?');
            $n->execute([$id]);
            flash('galat', 'Kategori "' . $name . '" tidak bisa dihapus karena masih dipakai ' . (int) $n->fetchColumn() . ' laporan.');
        } else {
            error_log('[LaPhish] Hapus kategori gagal: ' . $e->getMessage());
            flash('galat', 'Kategori gagal dihapus karena gangguan sistem.');
        }
    }
    redirect('admin/kategori.php');
}

$rows = $pdo->query(
    'SELECT c.category_id, c.name, c.description, COUNT(r.report_id) AS report_count
     FROM categories c LEFT JOIN reports r ON r.category_id = c.category_id
     GROUP BY c.category_id, c.name, c.description ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Kategori';
$activeMenu = 'kategori';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Kategori</h1><a class="btn btn-primary" href="<?= url('admin/kategori_form.php') ?>">Tambah kategori</a></div>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th class="ps-3">Nama</th><th>Deskripsi</th><th class="text-center lp-kol-angka">Laporan</th><th class="pe-3 lp-kol-aksi"></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="ps-3 fw-semibold"><?= e($r['name']) ?></td>
                    <td class="small<?= $r['description'] ? '' : ' text-muted-lp' ?>"><?= $r['description'] ? e($r['description']) : '' ?></td>
                    <td class="text-center"><?= (int) $r['report_count'] ?></td>
                    <td class="pe-3 text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/kategori_form.php?id=' . (int) $r['category_id']) ?>">Edit</a>
                        <form method="post" action="<?= url('admin/kategori.php') ?>" class="d-inline" data-confirm="Hapus kategori &quot;<?= e($r['name']) ?>&quot;?">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $r['category_id'] ?>">
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
