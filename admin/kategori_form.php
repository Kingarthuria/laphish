<?php
/**
 * A9: Form kategori (FR-11 Create/Update)
 * Deskripsi tampil sebagai teks bantuan di formulir lapor publik.
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$editing = $id > 0;
$item = ['name' => '', 'description' => ''];

if ($editing) {
    $stmt = $pdo->prepare('SELECT name, description FROM categories WHERE category_id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('galat', 'Kategori tidak ditemukan.');
        redirect('admin/kategori.php');
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['name'] = trim((string) ($_POST['name'] ?? ''));
    $item['description'] = trim((string) ($_POST['description'] ?? ''));

    if ($item['name'] === '' || mb_strlen($item['name']) > 100) {
        $errors[] = 'Nama kategori wajib diisi (maksimal 100 karakter).';
    }
    if (mb_strlen($item['description']) > 500) {
        $errors[] = 'Deskripsi terlalu panjang (maksimal 500 karakter).';
    }

    if (!$errors) {
        $desc = $item['description'] !== '' ? $item['description'] : null;
        try {
            if ($editing) {
                $pdo->prepare('UPDATE categories SET name = ?, description = ? WHERE category_id = ?')->execute([$item['name'], $desc, $id]);
                flash('sukses', 'Kategori "' . $item['name'] . '" berhasil diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO categories (name, description) VALUES (?, ?)')->execute([$item['name'], $desc]);
                flash('sukses', 'Kategori "' . $item['name'] . '" berhasil ditambahkan.');
            }
            redirect('admin/kategori.php');
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                $errors[] = 'Nama kategori sudah ada.';
            } else {
                error_log('[LaPhish] Simpan kategori gagal: ' . $e->getMessage());
                $errors[] = 'Kategori gagal disimpan karena gangguan sistem.';
            }
        }
    }
}

$pageTitle = $editing ? 'Edit kategori' : 'Tambah kategori';
$activeMenu = 'kategori';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/kategori.php') ?>" class="text-decoration-none">Kembali ke daftar kategori</a></div>
<div class="page-head"><h1 class="h4 fw-bold mb-0"><?= $editing ? 'Edit Kategori' : 'Tambah Kategori' ?></h1></div>
<div class="card p-4" style="max-width: 560px">
    <form method="post" action="<?= url('admin/kategori_form.php' . ($editing ? '?id=' . $id : '')) ?>" novalidate>
        <div class="mb-3">
            <label class="form-label" for="name">Nama <span class="req">*</span></label>
            <input class="form-control<?= $errors ? ' is-invalid' : '' ?>" id="name" name="name" maxlength="100" value="<?= e($item['name']) ?>" required>
            <?php if ($errors): ?><div class="invalid-feedback"><?= e(implode(' ', $errors)) ?></div><?php else: ?><div class="form-text">Harus unik.</div><?php endif; ?>
        </div>
        <div class="mb-3">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea class="form-control" id="description" name="description" rows="3" maxlength="500"><?= e((string) $item['description']) ?></textarea>
            <div class="form-text">Tampil sebagai teks bantuan di formulir lapor.</div>
        </div>
        <div class="d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="<?= url('admin/kategori.php') ?>">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
