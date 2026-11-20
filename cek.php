<?php
/**
 * P4 — Cek indikator (FR-05)
 * Nilai dinormalisasi sesuai jenisnya lalu dicocokkan dengan tabel indicators.
 * Hasil ditentukan oleh verification_status (docs/02a_desain_database.md bagian 7).
 * Memakai POST agar nilai yang dicek tidak tersimpan di alamat URL atau riwayat browser.
 */
require __DIR__ . '/includes/init.php';

$type = 'url';
$value = '';
$error = null;
$result = null; // ['kind' => phishing|belum|bukan_phishing|none, 'value' => ..., 'reports' => n]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = (string) ($_POST['type'] ?? 'url');
    $value = trim((string) ($_POST['value'] ?? ''));

    if (!isset(INDICATOR_TYPES[$type])) {
        $error = 'Pilih jenis yang tersedia.';
    } else {
        $normalized = normalize_indicator($type, $value);
        $error = validate_indicator($type, $normalized);
        if (!$error) {
            $stmt = db()->prepare(
                'SELECT i.verification_status, COUNT(ri.report_id) AS total
                 FROM indicators i
                 LEFT JOIN report_indicators ri ON ri.indicator_id = i.indicator_id
                 WHERE i.type = ? AND i.value = ?
                 GROUP BY i.indicator_id, i.verification_status'
            );
            $stmt->execute([$type, $normalized]);
            $row = $stmt->fetch();
            $result = [
                'kind'    => $row ? $row['verification_status'] : 'none',
                'value'   => $normalized,
                'reports' => $row ? (int) $row['total'] : 0,
            ];
        }
    }
}

$pageTitle = 'Cek indikator';
$activeNav = 'cek';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5" style="max-width: 800px">
    <h1 class="h3 fw-bold">Cek Tautan, Email, atau Nomor</h1>
    <p class="text-muted-lp"><i class="bi bi-exclamation-circle me-1"></i>Jangan klik tautannya. Salin, lalu tempel di sini.</p>

    <div class="card p-4 mb-4">
        <form method="post" action="<?= url('cek.php') ?>" novalidate>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="type">Jenis <span class="req">*</span></label>
                    <select class="form-select" id="type" name="type">
                        <?php foreach (INDICATOR_TYPES as $val => $label): ?>
                            <option value="<?= $val ?>"<?= $type === $val ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label" for="value">Nilai <span class="req">*</span></label>
                    <input class="form-control<?= $error ? ' is-invalid' : '' ?>" id="value" name="value" maxlength="500" value="<?= e($value) ?>" placeholder="Contoh: https://contoh.example/login" required>
                    <?php if ($error): ?><div class="invalid-feedback"><?= e($error) ?></div><?php endif; ?>
                </div>
                <div class="col-md-auto"><button class="btn btn-primary w-100" type="submit"><i class="bi bi-search me-1"></i>Cek</button></div>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
        <?php $shown = e(defang($result['value'])); ?>
        <?php if ($result['kind'] === 'phishing'): ?>
            <div class="res res-red" role="status">
                <div class="t fw-bold fs-5"><i class="bi bi-x-octagon-fill me-1"></i>Terverifikasi phishing</div>
                <div class="lp-defang my-1"><?= $shown ?></div>
                <div>Jangan klik, jangan isi data apa pun, dan jangan balas pesannya.<?= $result['reports'] > 0 ? ' Tercatat di ' . $result['reports'] . ' laporan.' : '' ?></div>
            </div>
        <?php elseif ($result['kind'] === 'belum'): ?>
            <div class="res res-orange" role="status">
                <div class="t fw-bold fs-5"><i class="bi bi-hourglass-split me-1"></i>Tercatat, masih ditinjau</div>
                <div class="lp-defang my-1"><?= $shown ?></div>
                <div><?= $result['reports'] > 0 ? 'Sudah dilaporkan (' . $result['reports'] . ' laporan) dan' : 'Sudah tercatat dan' ?> sedang diperiksa unit TI. Tetap waspada.</div>
            </div>
        <?php elseif ($result['kind'] === 'bukan_phishing'): ?>
            <div class="res res-green" role="status">
                <div class="t fw-bold fs-5"><i class="bi bi-shield-check me-1"></i>Sudah diperiksa, tidak terbukti phishing</div>
                <div class="lp-defang my-1"><?= $shown ?></div>
                <div>Unit TI tidak menemukan tanda phishing. Tetap waspada terhadap permintaan data pribadi.</div>
            </div>
        <?php else: ?>
            <div class="res res-grey" role="status">
                <div class="t fw-bold fs-5"><i class="bi bi-question-circle me-1"></i>Belum ada data</div>
                <div class="lp-defang my-1"><?= $shown ?></div>
                <div class="mb-2">Belum ada laporan atau data tentang nilai ini. Ini <strong>bukan</strong> jaminan aman.</div>
                <form method="post" action="<?= url('lapor.php') ?>">
                    <input type="hidden" name="action" value="prefill">
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                    <input type="hidden" name="value" value="<?= e($value) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-flag me-1"></i>Laporkan nilai ini</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
