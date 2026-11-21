<?php
/**
 * P4: Cek indikator (FR-05)
 * Nilai dinormalisasi sesuai jenisnya lalu dicocokkan dengan tabel indicators.
 * Hasil ditentukan oleh verification_status (docs/02a_desain_database.md bagian 7).
 * Memakai POST agar nilai yang dicek tidak tersimpan di alamat URL atau riwayat browser.
 */
require __DIR__ . '/includes/init.php';

$type = 'url';
$value = '';
$error = null;
$result = null; // ['kind' => phishing|belum|bukan_phishing|none, 'value' => ..., 'reports' => n]

// Petunjuk dan contoh isian mengikuti jenis yang dipilih (diganti oleh assets/js/app.js saat pilihan berubah)
$cekPetunjuk = [
    'url'   => ['hint' => 'Jangan klik tautannya. Salin, lalu tempel di sini.', 'placeholder' => 'Contoh: https://contoh.example/login'],
    'email' => ['hint' => 'Jangan klik tautan atau lampiran di emailnya. Salin alamat pengirimnya, lalu tempel di sini.', 'placeholder' => 'Contoh: penipu@contoh.example'],
    'phone' => ['hint' => 'Jangan balas atau telepon nomornya. Salin nomornya, lalu tempel di sini.', 'placeholder' => 'Contoh: 0812-3456-7890'],
];

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

// Nilai yang diperiksa ditampilkan defanged. Untuk URL, alamat dibedah: bagian domain (pemilik situs) disorot.
$valueBlock = '';
if ($result) {
    if ($type === 'url') {
        $p = url_segments($result['value']);
        $valueBlock = '<div class="lp-url my-2">';
        foreach ([[$p['sub'], 'subdomain: bebas dibuat', 'dim'], [$p['domain'], 'domain: pemilik situs', 'key'], [$p['port'], 'port', 'dim'], [$p['rest'], 'jalur', 'dim']] as [$text, $label, $cls]) {
            if ($text !== '') {
                $valueBlock .= '<div class="lp-seg ' . $cls . '"><code>' . e(defang($text)) . '</code><small>' . e($label) . '</small></div>';
            }
        }
        $valueBlock .= '</div>';
    } else {
        $valueBlock = '<div class="lp-defang my-1">' . e(defang($result['value'])) . '</div>';
    }
}

$pageTitle = 'Cek indikator';
$activeNav = 'cek';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5" style="max-width: 800px">
    <h1 class="h3 fw-bold">Cek Tautan, Email, atau Nomor</h1>
    <p class="text-muted-lp" id="cekHint"><?= e(($cekPetunjuk[$type] ?? $cekPetunjuk['url'])['hint']) ?></p>

    <div class="card p-4 mb-4">
        <form method="post" action="<?= url('cek.php') ?>" novalidate>
            <div class="row g-2 align-items-start">
                <div class="col-md-3">
                    <label class="form-label" for="type">Jenis <span class="req">*</span></label>
                    <select class="form-select" id="type" name="type" data-cek-petunjuk>
                        <?php foreach (INDICATOR_TYPES as $val => $label): ?>
                            <option value="<?= $val ?>" data-hint="<?= e($cekPetunjuk[$val]['hint']) ?>" data-placeholder="<?= e($cekPetunjuk[$val]['placeholder']) ?>"<?= $type === $val ? ' selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md">
                    <label class="form-label" for="value">Nilai <span class="req">*</span></label>
                    <input class="form-control<?= $error ? ' is-invalid' : '' ?>" id="value" name="value" maxlength="500" value="<?= e($value) ?>" placeholder="<?= e(($cekPetunjuk[$type] ?? $cekPetunjuk['url'])['placeholder']) ?>" required>
                    <?php if ($error): ?><div class="invalid-feedback"><?= e($error) ?></div><?php endif; ?>
                </div>
                <div class="col-md-auto"><span class="form-label d-none d-md-block invisible" aria-hidden="true">&nbsp;</span><button class="btn btn-primary w-100" type="submit">Cek</button></div>
            </div>
        </form>
    </div>

    <?php if ($result): ?>
        <?php if ($result['kind'] === 'phishing'): ?>
            <div class="res res-red" role="status">
                <div class="t fw-bold fs-5">Terverifikasi phishing</div>
                <?= $valueBlock ?>
                <div>Jangan klik, jangan isi data apa pun, dan jangan balas pesannya.<?= $result['reports'] > 0 ? ' Tercatat di ' . $result['reports'] . ' laporan.' : '' ?></div>
            </div>
        <?php elseif ($result['kind'] === 'belum'): ?>
            <div class="res res-orange" role="status">
                <div class="t fw-bold fs-5">Tercatat, masih ditinjau</div>
                <?= $valueBlock ?>
                <div><?= $result['reports'] > 0 ? 'Sudah dilaporkan (' . $result['reports'] . ' laporan) dan' : 'Sudah tercatat dan' ?> sedang diperiksa unit TI. Tetap waspada.</div>
            </div>
        <?php elseif ($result['kind'] === 'bukan_phishing'): ?>
            <div class="res res-green" role="status">
                <div class="t fw-bold fs-5">Sudah diperiksa, tidak terbukti phishing</div>
                <?= $valueBlock ?>
                <div>Unit TI tidak menemukan tanda phishing. Tetap waspada terhadap permintaan data pribadi.</div>
            </div>
        <?php else: ?>
            <div class="res res-grey" role="status">
                <div class="t fw-bold fs-5">Belum ada data</div>
                <?= $valueBlock ?>
                <div class="mb-2">Belum ada laporan atau data tentang nilai ini. Ini <strong>bukan</strong> jaminan aman.</div>
                <form method="post" action="<?= url('lapor.php') ?>">
                    <input type="hidden" name="action" value="prefill">
                    <input type="hidden" name="type" value="<?= e($type) ?>">
                    <input type="hidden" name="value" value="<?= e($value) ?>">
                    <button class="btn btn-outline-primary btn-sm" type="submit">Laporkan nilai ini</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <h2 class="h5 fw-bold mt-5 mb-3">Arti setiap hasil</h2>
    <ul class="lp-keys">
        <li><span class="st st-red">Terverifikasi phishing</span><span class="text-muted-lp">Unit TI sudah memastikan ini berbahaya. Jangan dibuka.</span></li>
        <li><span class="st st-orange">Tercatat, masih ditinjau</span><span class="text-muted-lp">Sudah ada yang melapor, hasil belum final. Anggap berbahaya dulu.</span></li>
        <li><span class="st st-green">Sudah diperiksa, tidak terbukti phishing</span><span class="text-muted-lp">Sudah diperiksa dan tidak ditemukan bukti. Tetap periksa pengirimnya.</span></li>
        <li><span class="st st-grey">Belum ada data</span><span class="text-muted-lp">Belum pernah dilaporkan. Itu belum berarti aman.</span></li>
    </ul>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
