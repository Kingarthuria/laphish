<?php
/**
 * A11 — Form kanal (FR-12 Create/Update)
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$editing = $id > 0;
$item = ['name' => ''];

if ($editing) {
    $stmt = $pdo->prepare('SELECT name FROM channels WHERE channel_id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('galat', 'Kanal tidak ditemukan.');
        redirect('admin/kanal.php');
    }
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['name'] = trim((string) ($_POST['name'] ?? ''));
    if ($item['name'] === '' || mb_strlen($item['name']) > 50) {
        $error = 'Nama kanal wajib diisi (maksimal 50 karakter).';
    } else {
        try {
            if ($editing) {
                $pdo->prepare('UPDATE channels SET name = ? WHERE channel_id = ?')->execute([$item['name'], $id]);
                flash('sukses', 'Kanal "' . $item['name'] . '" berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO channels (name) VALUES (?)')->execute([$item['name']]);
                flash('sukses', 'Kanal "' . $item['name'] . '" berhasil ditambahkan.');
            }
            redirect('admin/kanal.php');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $error = 'Nama kanal sudah ada.';
            } else {
                error_log('[LaPhish] Simpan kanal gagal: ' . $e->getMessage());
                $error = 'Kanal gagal disimpan karena gangguan sistem.';
            }
        }
    }
}

$pageTitle = $editing ? 'Edit kanal' : 'Tambah kanal';
$activeMenu = 'kanal';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/kanal.php') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke daftar kanal</a></div>
<div class="page-head"><h1 class="h4 fw-bold mb-0"><?= $editing ? 'Edit Kanal' : 'Tambah Kanal' ?></h1></div>
<div class="card p-4" style="max-width: 480px">
    <form method="post" action="<?= url('admin/kanal_form.php' . ($editing ? '?id=' . $id : '')) ?>" novalidate>
        <div class="mb-3">
            <label class="form-label" for="name">Nama <span class="req">*</span></label>
            <input class="form-control<?= $error ? ' is-invalid' : '' ?>" id="name" name="name" maxlength="50" value="<?= e($item['name']) ?>" required>
            <?php if ($error): ?><div class="invalid-feedback"><?= e($error) ?></div><?php else: ?><div class="form-text">Harus unik.</div><?php endif; ?>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="<?= url('admin/kanal.php') ?>">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
