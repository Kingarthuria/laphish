<?php
/**
 * A7 — Form indikator (FR-10 Create/Update)
 * Mode tambah : jenis, nilai, status verifikasi, catatan sumber.
 * Mode edit   : jenis dan nilai dikunci; hanya status verifikasi dan catatan yang bisa diubah
 *               (aturan integritas nomor 8 di docs/02a_desain_database.md).
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$editing = $id > 0;
$admin = current_admin()['id'];

$item = ['type' => 'url', 'value' => '', 'verification_status' => 'belum', 'note' => '', 'verified_at' => null, 'verifier' => null];
if ($editing) {
    $stmt = $pdo->prepare('SELECT i.*, a.full_name AS verifier FROM indicators i LEFT JOIN admins a ON a.admin_id = i.verified_by WHERE i.indicator_id = ?');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        flash('galat', 'Indikator tidak ditemukan.');
        redirect('admin/indikator.php');
    }
}

$errors = [];
$existingId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vs = (string) ($_POST['verification_status'] ?? '');
    $note = trim((string) ($_POST['note'] ?? ''));
    $item['verification_status'] = $vs;
    $item['note'] = $note;
    if (!isset(VERIFICATION_STATUSES[$vs])) {
        $errors[] = 'Pilih status verifikasi.';
    }
    if (mb_strlen($note) > 255) {
        $errors[] = 'Catatan terlalu panjang (maksimal 255 karakter).';
    }

    if (!$editing) {
        $item['type'] = (string) ($_POST['type'] ?? '');
        $item['value'] = trim((string) ($_POST['value'] ?? ''));
        if (!isset(INDICATOR_TYPES[$item['type']])) {
            $errors[] = 'Pilih jenis indikator.';
        } else {
            $normalized = normalize_indicator($item['type'], $item['value']);
            if ($msg = validate_indicator($item['type'], $normalized)) {
                $errors[] = $msg;
            } else {
                $check = $pdo->prepare('SELECT indicator_id FROM indicators WHERE type = ? AND value = ?');
                $check->execute([$item['type'], $normalized]);
                $existingId = $check->fetchColumn() ?: null;
                if ($existingId) {
                    $errors[] = 'Indikator ini sudah tercatat.';
                }
            }
        }
    }

    if (!$errors) {
        $verified = $vs !== 'belum';
        try {
            if ($editing) {
                // Pencatat verifikasi (verified_by/at) hanya diperbarui jika statusnya berubah,
                // dan dikosongkan jika status kembali ke "belum".
                // MySQL/MariaDB mengevaluasi SET dari kiri ke kanan, jadi kolom verified_* dihitung
                // memakai status lama sebelum verification_status diubah.
                $stmt = $pdo->prepare(
                    'UPDATE indicators SET
                        verified_by = CASE WHEN ? = 0 THEN NULL WHEN verification_status = ? THEN verified_by ELSE ? END,
                        verified_at = CASE WHEN ? = 0 THEN NULL WHEN verification_status = ? THEN verified_at ELSE NOW() END,
                        verification_status = ?, note = ?
                     WHERE indicator_id = ?'
                );
                $stmt->execute([(int) $verified, $vs, $admin, (int) $verified, $vs, $vs, $note !== '' ? $note : null, $id]);
                flash('sukses', 'Indikator berhasil diperbarui.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO indicators (type, value, verification_status, note, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ' . ($verified ? 'NOW()' : 'NULL') . ')');
                $stmt->execute([$item['type'], $normalized, $vs, $note !== '' ? $note : null, $verified ? $admin : null]);
                flash('sukses', 'Indikator berhasil ditambahkan.');
            }
            redirect('admin/indikator.php');
        } catch (PDOException $e) {
            error_log('[LaPhish] Simpan indikator gagal: ' . $e->getMessage());
            $errors[] = 'Indikator gagal disimpan karena gangguan sistem.';
        }
    }
}

$pageTitle = $editing ? 'Edit indikator' : 'Tambah indikator';
$activeMenu = 'indikator';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="small mb-2"><a href="<?= url('admin/indikator.php') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke daftar indikator</a></div>
<div class="page-head"><h1 class="h4 fw-bold mb-0"><?= $editing ? 'Edit Indikator' : 'Tambah Indikator' ?></h1></div>

<div class="card p-4" style="max-width: 640px">
    <?php if ($errors): ?>
        <div class="alert alert-err small" role="alert">
            <?php foreach ($errors as $err): ?><div><i class="bi bi-x-circle-fill me-1"></i><?= e($err) ?></div><?php endforeach; ?>
            <?php if ($existingId): ?><div class="mt-1"><a href="<?= url('admin/indikator_form.php?id=' . (int) $existingId) ?>">Lihat data yang sudah ada</a></div><?php endif; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="<?= url('admin/indikator_form.php' . ($editing ? '?id=' . $id : '')) ?>" novalidate>
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label" for="type">Jenis<?= $editing ? '' : ' <span class="req">*</span>' ?></label>
                <select class="form-select" id="type" name="type"<?= $editing ? ' disabled' : '' ?>>
                    <?php foreach (INDICATOR_TYPES as $k => $l): ?><option value="<?= $k ?>"<?= $item['type'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label" for="verification_status">Verifikasi <span class="req">*</span></label>
                <select class="form-select" id="verification_status" name="verification_status">
                    <?php foreach (VERIFICATION_STATUSES as $k => $v): ?><option value="<?= $k ?>"<?= $item['verification_status'] === $k ? ' selected' : '' ?>><?= e($v['label']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="value">Nilai<?= $editing ? '' : ' <span class="req">*</span>' ?></label>
                <?php if ($editing): ?>
                    <input class="form-control font-monospace" id="value" value="<?= e(defang($item['value'])) ?>" disabled>
                    <div class="form-text"><i class="bi bi-lock me-1"></i>Jenis dan nilai tidak bisa diubah. Jika salah, tambahkan indikator baru.</div>
                <?php else: ?>
                    <input class="form-control" id="value" name="value" maxlength="500" value="<?= e($item['value']) ?>" placeholder="https://contoh.example/login">
                    <div class="form-text">Nilai otomatis dinormalisasi (huruf kecil, tanpa http/https dan www).</div>
                <?php endif; ?>
            </div>
            <div class="col-12">
                <label class="form-label" for="note">Catatan / sumber</label>
                <input class="form-control" id="note" name="note" maxlength="255" value="<?= e((string) $item['note']) ?>" placeholder="Contoh: peringatan resmi unit TI">
            </div>
        </div>
        <?php if ($editing && $item['verified_at']): ?>
            <div class="small text-muted-lp mt-3"><i class="bi bi-person-check me-1"></i>Diverifikasi oleh <?= e($item['verifier'] ?? '(akun dihapus)') ?> · <?= e(format_datetime($item['verified_at'])) ?></div>
        <?php endif; ?>
        <div class="d-flex gap-2 justify-content-end mt-4">
            <a class="btn btn-outline-secondary" href="<?= url('admin/indikator.php') ?>">Batal</a>
            <button class="btn btn-primary" type="submit">Simpan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
