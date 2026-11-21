<?php
/**
 * P2 — Formulir lapor (FR-02)
 * GET  : tampilkan formulir kosong.
 * POST : action=prefill (dari halaman Cek) mengisi indikator pertama;
 *        action=kirim memvalidasi lalu menyimpan laporan dalam SATU transaksi
 *        (reporters, reports, indicators, report_indicators, status_histories).
 */
require __DIR__ . '/includes/init.php';

const MAX_INDICATORS = 5;
// Contoh isian per jenis indikator (placeholder). assets/js/app.js menggantinya saat jenis pada satu baris diubah.
const CONTOH_INDIKATOR = ['url' => 'https://contoh.example/login', 'email' => 'penipu@contoh.example', 'phone' => '0812-3456-7890'];

$categories = db()->query('SELECT category_id, name, description FROM categories ORDER BY category_id')->fetchAll();
$channels = db()->query('SELECT channel_id, name FROM channels ORDER BY channel_id')->fetchAll();

$old = [
    'channel_id'    => '',
    'category_id'   => '',
    'incident_date' => '',
    'chronology'    => '',
    'name'          => '',
    'email'         => '',
    'indicators'    => [['type' => 'url', 'value' => '']],
];
$errors = [];

$action = $_POST['action'] ?? '';

// Datang dari halaman Cek ("Laporkan nilai ini"): isi indikator pertama saja
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'prefill') {
    $type = $_POST['type'] ?? 'url';
    $old['indicators'] = [['type' => isset(INDICATOR_TYPES[$type]) ? $type : 'url', 'value' => trim((string) ($_POST['value'] ?? ''))]];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'kirim') {
    // ---------- Ambil input ----------
    $old['channel_id'] = (string) ($_POST['channel_id'] ?? '');
    $old['category_id'] = (string) ($_POST['category_id'] ?? '');
    $old['incident_date'] = trim((string) ($_POST['incident_date'] ?? ''));
    $old['chronology'] = trim((string) ($_POST['chronology'] ?? ''));
    $old['name'] = trim((string) ($_POST['name'] ?? ''));
    $old['email'] = trim((string) ($_POST['email'] ?? ''));
    $types = (array) ($_POST['ind_type'] ?? []);
    $values = (array) ($_POST['ind_value'] ?? []);
    $old['indicators'] = [];
    foreach ($values as $i => $v) {
        $old['indicators'][] = ['type' => (string) ($types[$i] ?? 'url'), 'value' => trim((string) $v)];
    }
    if (!$old['indicators']) {
        $old['indicators'] = [['type' => 'url', 'value' => '']];
    }

    // ---------- Validasi (NFR-07) ----------
    $channelIds = array_column($channels, 'channel_id');
    $categoryIds = array_column($categories, 'category_id');
    if (!in_array((int) $old['channel_id'], array_map('intval', $channelIds), true)) {
        $errors[] = 'Pilih kanal.';
    }
    if (!in_array((int) $old['category_id'], array_map('intval', $categoryIds), true)) {
        $errors[] = 'Pilih kategori.';
    }
    $date = DateTime::createFromFormat('!Y-m-d', $old['incident_date']);
    if (!$date || $date->format('Y-m-d') !== $old['incident_date']) {
        $errors[] = 'Tanggal kejadian wajib diisi dengan format yang benar.';
    } elseif ($date > new DateTime('today')) {
        $errors[] = 'Tanggal kejadian tidak boleh di masa depan.';
    }
    $chronoLen = mb_strlen($old['chronology']);
    if ($chronoLen < 10) {
        $errors[] = 'Kronologi wajib diisi (minimal 10 karakter).';
    } elseif ($chronoLen > 3000) {
        $errors[] = 'Kronologi terlalu panjang (maksimal 3.000 karakter).';
    }

    // Indikator: buang baris kosong, normalisasi, validasi, dan hilangkan duplikat
    $cleanIndicators = [];
    $filled = array_values(array_filter($old['indicators'], fn($r) => $r['value'] !== ''));
    if (count($filled) < 1) {
        $errors[] = 'Isi minimal 1 indikator (tautan, email, atau nomor).';
    } elseif (count($filled) > MAX_INDICATORS) {
        $errors[] = 'Maksimal ' . MAX_INDICATORS . ' indikator per laporan.';
    } else {
        foreach ($filled as $n => $row) {
            if (!isset(INDICATOR_TYPES[$row['type']])) {
                $errors[] = 'Indikator ' . ($n + 1) . ': jenis tidak dikenal.';
                continue;
            }
            $normalized = normalize_indicator($row['type'], $row['value']);
            if ($msg = validate_indicator($row['type'], $normalized)) {
                $errors[] = 'Indikator ' . ($n + 1) . ': ' . $msg;
                continue;
            }
            $key = $row['type'] . '|' . $normalized;
            // Jika indikator yang sama diisi dua kali, simpan teks asli dari isian pertama saja
            $cleanIndicators[$key] ??=['type' => $row['type'], 'value' => $normalized, 'raw' => mb_substr($row['value'], 0, 500)];
        }
    }

    if (mb_strlen($old['name']) > 100) {
        $errors[] = 'Nama terlalu panjang (maksimal 100 karakter).';
    }
    $emailNorm = mb_strtolower($old['email']);
    if ($emailNorm !== '' && (!filter_var($emailNorm, FILTER_VALIDATE_EMAIL) || mb_strlen($emailNorm) > 150)) {
        $errors[] = 'Format email pelapor tidak valid.';
    }

    // ---------- Simpan dalam satu transaksi ----------
    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // 1. Pelapor (opsional). Email yang sama memakai baris yang sudah ada (nama lama tidak ditimpa).
            $reporterId = null;
            if ($emailNorm !== '') {
                $stmt = $pdo->prepare('SELECT reporter_id FROM reporters WHERE email = ?');
                $stmt->execute([$emailNorm]);
                $reporterId = $stmt->fetchColumn() ?: null;
            }
            if ($reporterId === null && ($old['name'] !== '' || $emailNorm !== '')) {
                $stmt = $pdo->prepare('INSERT INTO reporters (name, email) VALUES (?, ?)');
                $stmt->execute([$old['name'] !== '' ? $old['name'] : null, $emailNorm !== '' ? $emailNorm : null]);
                $reporterId = (int) $pdo->lastInsertId();
            }

            // 2. Laporan dengan kode tiket acak (dibuat ulang jika kebetulan sudah dipakai)
            $insertReport = $pdo->prepare('INSERT INTO reports (ticket_code, reporter_id, category_id, channel_id, incident_date, chronology) VALUES (?, ?, ?, ?, ?, ?)');
            $ticket = null;
            for ($try = 0; $try < 5 && $ticket === null; $try++) {
                $candidate = generate_ticket();
                $check = $pdo->prepare('SELECT 1 FROM reports WHERE ticket_code = ?');
                $check->execute([$candidate]);
                if (!$check->fetchColumn()) {
                    $ticket = $candidate;
                }
            }
            if ($ticket === null) {
                throw new RuntimeException('Gagal membuat kode tiket unik.');
            }
            $insertReport->execute([$ticket, $reporterId, (int) $old['category_id'], (int) $old['channel_id'], $old['incident_date'], $old['chronology']]);
            $reportId = (int) $pdo->lastInsertId();

            // 3. Indikator (pakai yang sudah ada jika sama) + 4. penghubung
            $findInd = $pdo->prepare('SELECT indicator_id FROM indicators WHERE type = ? AND value = ?');
            $addInd = $pdo->prepare('INSERT INTO indicators (type, value) VALUES (?, ?)');
            $link = $pdo->prepare('INSERT INTO report_indicators (report_id, indicator_id, raw_value) VALUES (?, ?, ?)');
            foreach ($cleanIndicators as $ind) {
                $findInd->execute([$ind['type'], $ind['value']]);
                $indicatorId = $findInd->fetchColumn();
                if (!$indicatorId) {
                    $addInd->execute([$ind['type'], $ind['value']]);
                    $indicatorId = $pdo->lastInsertId();
                }
                $link->execute([$reportId, (int) $indicatorId, $ind['raw']]);
            }

            // 5. Status awal "baru" (dibuat oleh sistem, admin_id NULL)
            $pdo->prepare("INSERT INTO status_histories (report_id, status, note) VALUES (?, 'baru', NULL)")->execute([$reportId]);

            $pdo->commit();
            $_SESSION['last_ticket'] = $ticket;
            redirect('terkirim.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[LaPhish] Gagal menyimpan laporan: ' . $e->getMessage());
            $errors[] = 'Laporan gagal disimpan karena gangguan sistem. Silakan coba lagi.';
        }
    }
}

$pageTitle = 'Laporkan Phishing';
$activeNav = 'lapor';
require __DIR__ . '/includes/layout_publik_atas.php';
?>
<div class="container py-5" style="max-width: 800px">
    <h1 class="h3 fw-bold">Laporkan Phishing</h1>
    <p class="text-muted-lp">Kolom bertanda <span class="req">*</span> wajib diisi.</p>

    <?php if ($errors): ?>
        <div class="alert alert-err" role="alert">
            <div class="fw-semibold mb-1">Laporan belum bisa dikirim:</div>
            <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('lapor.php') ?>" novalidate>
        <input type="hidden" name="action" value="kirim">

        <div class="card p-4 mb-4">
            <h2 class="lp-sec mb-3"><span class="lp-no">1</span>Detail kejadian</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="channel_id">Kanal <span class="req">*</span></label>
                    <select class="form-select" id="channel_id" name="channel_id" required>
                        <option value="">Pilih kanal</option>
                        <?php foreach ($channels as $ch): ?>
                            <option value="<?= (int) $ch['channel_id'] ?>"<?= (string) $ch['channel_id'] === $old['channel_id'] ? ' selected' : '' ?>><?= e($ch['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="incident_date">Tanggal kejadian <span class="req">*</span></label>
                    <input type="date" class="form-control" id="incident_date" name="incident_date" max="<?= date('Y-m-d') ?>" value="<?= e($old['incident_date']) ?>" required>
                    <div class="form-text">Tidak boleh tanggal di masa depan.</div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="category_id">Kategori <span class="req">*</span></label>
                    <select class="form-select" id="category_id" name="category_id" required data-help-target="categoryHelp">
                        <option value="" data-desc="Pilih jenis phishing yang paling sesuai.">Pilih kategori</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['category_id'] ?>" data-desc="<?= e($cat['description']) ?>"<?= (string) $cat['category_id'] === $old['category_id'] ? ' selected' : '' ?>><?= e($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text"><span id="categoryHelp">Pilih jenis phishing yang paling sesuai.</span></div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="chronology">Kronologi <span class="req">*</span></label>
                    <textarea class="form-control" id="chronology" name="chronology" rows="3" maxlength="3000" required placeholder="Ceritakan singkat: pesan apa yang diterima, dari siapa, dan apa yang diminta."><?= e($old['chronology']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <h2 class="lp-sec mb-1"><span class="lp-no">2</span>Indikator <span class="req">*</span></h2>
            <p class="small text-muted-lp">Tempel tautan, email, atau nomor dari pesan tersebut. Minimal 1, maksimal <?= MAX_INDICATORS ?>.</p>
            <div id="indicatorRows" data-max="<?= MAX_INDICATORS ?>">
                <?php foreach (array_slice($old['indicators'], 0, MAX_INDICATORS) as $i => $row): ?>
                    <div class="row g-2 mb-2 align-items-center indicator-row">
                        <div class="col-4 col-md-3">
                            <select class="form-select" name="ind_type[]" aria-label="Jenis indikator">
                                <?php foreach (INDICATOR_TYPES as $val => $label): ?>
                                    <option value="<?= $val ?>" data-placeholder="<?= e(CONTOH_INDIKATOR[$val]) ?>"<?= $row['type'] === $val ? ' selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col"><input class="form-control" name="ind_value[]" maxlength="500" value="<?= e($row['value']) ?>" placeholder="<?= e(CONTOH_INDIKATOR[$row['type']] ?? CONTOH_INDIKATOR['url']) ?>" aria-label="Nilai indikator"></div>
                        <div class="col-auto" style="width: 52px">
                            <button type="button" class="btn btn-outline-secondary remove-indicator<?= $i === 0 ? ' invisible' : '' ?>" title="Hapus baris" aria-label="Hapus baris"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addIndicator">Tambah indikator</button>
                <span class="small text-muted-lp ms-2"><span id="indicatorCount"><?= min(count($old['indicators']), MAX_INDICATORS) ?></span> dari <?= MAX_INDICATORS ?></span>
            </div>
        </div>

        <div class="card p-4 mb-4">
            <h2 class="lp-sec mb-1"><span class="lp-no">3</span>Identitas pelapor <span class="fw-normal text-muted-lp fs-6">(opsional)</span></h2>
            <p class="small text-muted-lp">Hanya dipakai unit TI untuk menghubungi Anda. Tidak ditampilkan di halaman publik.</p>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="name">Nama</label><input class="form-control" id="name" name="name" maxlength="100" value="<?= e($old['name']) ?>" placeholder="Boleh dikosongkan"></div>
                <div class="col-md-6"><label class="form-label" for="email">Email</label><input type="email" class="form-control" id="email" name="email" maxlength="150" value="<?= e($old['email']) ?>" placeholder="Boleh dikosongkan"></div>
            </div>
        </div>

        <button class="btn btn-primary btn-lg w-100" type="submit">Kirim laporan</button>
    </form>
</div>
<?php require __DIR__ . '/includes/layout_publik_bawah.php'; ?>
