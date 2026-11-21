<?php
/**
 * A3 — Daftar laporan (FR-08, Read + Cari)
 * Pencarian: kode tiket atau kata di kronologi.
 * Filter: status, kategori, rentang tanggal laporan masuk.
 * Laporan baru dibuat pengunjung lewat formulir publik (P2), jadi tidak ada tombol tambah.
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$categories = db()->query('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$categoryId = (int) ($_GET['category_id'] ?? 0);
$from = (string) ($_GET['from'] ?? '');
$to = (string) ($_GET['to'] ?? '');

$isDate = fn(string $d) => ($dt = DateTime::createFromFormat('!Y-m-d', $d)) && $dt->format('Y-m-d') === $d;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(r.ticket_code LIKE ? OR r.chronology LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like);
}
if (isset(REPORT_STATUSES[$status])) {
    $where[] = 's.status = ?';
    $params[] = $status;
} else {
    $status = '';
}
if ($categoryId > 0) {
    $where[] = 'r.category_id = ?';
    $params[] = $categoryId;
}
if ($from !== '' && $isDate($from)) {
    $where[] = 'r.created_at >= ?';
    $params[] = $from . ' 00:00:00';
} else {
    $from = '';
}
if ($to !== '' && $isDate($to)) {
    $where[] = 'r.created_at <= ?';
    $params[] = $to . ' 23:59:59';
} else {
    $to = '';
}

$from_join = ' FROM reports r
        JOIN categories c ON c.category_id = r.category_id
        JOIN channels ch ON ch.channel_id = r.channel_id
        JOIN v_report_status s ON s.report_id = r.report_id'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

// Jumlah laporan yang cocok dengan filter menentukan jumlah halaman
$count = db()->prepare('SELECT COUNT(*)' . $from_join);
$count->execute($params);
$cocok = (int) $count->fetchColumn();
[$halaman, $jumlahHalaman, $offset] = halaman_sekarang($cocok);

// LIMIT dan OFFSET dari bilangan bulat yang sudah dibatasi (bukan input mentah), jadi aman ditulis langsung
$sql = 'SELECT r.report_id, r.ticket_code, r.created_at, c.name AS category, ch.name AS channel, s.status,
               (SELECT COUNT(*) FROM report_indicators ri WHERE ri.report_id = r.report_id) AS indicator_count'
     . $from_join
     . ' ORDER BY r.created_at DESC, r.report_id DESC LIMIT ' . PER_HALAMAN . ' OFFSET ' . $offset;
$stmt = db()->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();
$total = (int) db()->query('SELECT COUNT(*) FROM reports')->fetchColumn();
$filterAktif = ['q' => $q, 'status' => $status, 'category_id' => $categoryId, 'from' => $from, 'to' => $to];

$pageTitle = 'Laporan';
$activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Laporan</h1></div>

<form class="card p-3 mb-3" method="get" action="<?= url('admin/laporan.php') ?>">
    <div class="row g-2 align-items-end">
        <div class="col-lg-4">
            <label class="form-label small mb-1" for="q">Cari</label>
            <div class="input-group"><span class="input-group-text"><i class="bi bi-search"></i></span><input class="form-control" id="q" name="q" value="<?= e($q) ?>" placeholder="Kode tiket atau kata di kronologi"></div>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">Semua</option>
                <?php foreach (REPORT_STATUSES as $key => $s): ?><option value="<?= $key ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($s['label']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label small mb-1" for="category_id">Kategori</label>
            <select class="form-select" id="category_id" name="category_id">
                <option value="0">Semua</option>
                <?php foreach ($categories as $c): ?><option value="<?= (int) $c['category_id'] ?>"<?= $categoryId === (int) $c['category_id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2"><label class="form-label small mb-1" for="from">Masuk dari</label><input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>"></div>
        <div class="col-6 col-lg-2"><label class="form-label small mb-1" for="to">Sampai</label><input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>"></div>
        <div class="col-12 d-flex gap-2 justify-content-end">
            <a class="btn btn-outline-secondary" href="<?= url('admin/laporan.php') ?>">Reset</a>
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
        </div>
    </div>
</form>

<?php if ($reports): ?>
<div class="small text-muted-lp mb-2">Laporan <?= rentang_baris($offset, count($reports)) ?> dari <?= $cocok ?><?= $cocok < $total ? ' yang cocok (seluruhnya ' . $total . ')' : '' ?>, terbaru di atas</div>
<?php endif; ?>
<div class="card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead><tr><th class="ps-3">Kode tiket</th><th>Tgl masuk</th><th>Kategori</th><th>Kanal</th><th class="text-center">Indikator</th><th>Status</th><th class="pe-3"></th></tr></thead>
            <tbody>
            <?php if (!$reports): ?>
                <tr><td colspan="7" class="text-center text-muted-lp py-4">Tidak ada laporan yang cocok dengan pencarian ini.</td></tr>
            <?php endif; ?>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td class="ps-3 font-monospace fw-semibold text-nowrap"><?= e($r['ticket_code']) ?></td>
                    <td><?= e(format_date($r['created_at'])) ?></td>
                    <td><?= e($r['category']) ?></td>
                    <td><?= e($r['channel']) ?></td>
                    <td class="text-center"><?= (int) $r['indicator_count'] ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="pe-3 text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/laporan_detail.php?id=' . (int) $r['report_id']) ?>">Detail</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?= navigasi_halaman('admin/laporan.php', $filterAktif, $halaman, $jumlahHalaman) ?>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
