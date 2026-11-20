<?php
/**
 * A2 — Dashboard rekap (FR-07)
 * Kartu jumlah laporan per status dan tabel angka per kategori, kanal, dan bulan.
 * Semua angka dihitung langsung dengan kueri GROUP BY, tanpa tabel tambahan.
 */
require __DIR__ . '/../includes/init.php';
require_admin();

$pdo = db();

$byStatus = array_fill_keys(array_keys(REPORT_STATUSES), 0);
foreach ($pdo->query('SELECT status, COUNT(*) AS n FROM v_report_status GROUP BY status') as $row) {
    $byStatus[$row['status']] = (int) $row['n'];
}
$total = array_sum($byStatus);

$byCategory = $pdo->query(
    'SELECT c.name, COUNT(r.report_id) AS n
     FROM categories c LEFT JOIN reports r ON r.category_id = c.category_id
     GROUP BY c.category_id, c.name ORDER BY n DESC, c.name'
)->fetchAll();

$byChannel = $pdo->query(
    'SELECT ch.name, COUNT(r.report_id) AS n
     FROM channels ch LEFT JOIN reports r ON r.channel_id = ch.channel_id
     GROUP BY ch.channel_id, ch.name ORDER BY n DESC, ch.name'
)->fetchAll();

// Enam bulan terakhir berdasarkan tanggal laporan masuk (termasuk bulan tanpa laporan)
$monthStart = (new DateTime('first day of this month'))->modify('-5 months')->setTime(0, 0);
$stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS n FROM reports WHERE created_at >= ? GROUP BY ym");
$stmt->execute([$monthStart->format('Y-m-d H:i:s')]);
$monthCounts = array_column($stmt->fetchAll(), 'n', 'ym');
$monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$byMonth = [];
for ($i = 5; $i >= 0; $i--) {
    $m = (new DateTime('first day of this month'))->modify("-$i months");
    $byMonth[] = ['label' => $monthNames[(int) $m->format('n')] . ' ' . $m->format('Y'), 'n' => (int) ($monthCounts[$m->format('Y-m')] ?? 0)];
}
$byMonth = array_reverse($byMonth);

$cards = [
    ['Total laporan', $total, 'c-dark', 'admin/laporan.php'],
    ['Baru', $byStatus['baru'], 'c-purple', 'admin/laporan.php?status=baru'],
    ['Ditinjau', $byStatus['ditinjau'], 'c-orange', 'admin/laporan.php?status=ditinjau'],
    ['Valid', $byStatus['valid'], 'c-red', 'admin/laporan.php?status=valid'],
    ['Tidak valid', $byStatus['tidak_valid'], 'c-grey', 'admin/laporan.php?status=tidak_valid'],
];

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
require __DIR__ . '/../includes/layout_admin_atas.php';
?>
<div class="page-head"><h1 class="h4 fw-bold mb-0">Dashboard</h1><span class="small text-muted-lp">Data per <?= e(date('d/m/Y H.i')) ?></span></div>

<div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
    <?php foreach ($cards as [$label, $n, $class, $href]): ?>
        <div class="col">
            <a href="<?= url($href) ?>" class="text-decoration-none">
                <div class="card stat <?= $class ?> p-3 h-100"><div class="small text-muted-lp mb-1"><?= e($label) ?></div><div class="num"><?= $n ?></div></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <?php foreach ([['Per kategori', 'bi-tags', 'Kategori', $byCategory], ['Per kanal', 'bi-broadcast', 'Kanal', $byChannel]] as [$title, $icon, $col, $rows]): ?>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="p-3 pb-2 fw-bold"><i class="bi <?= $icon ?> me-1" style="color: var(--lp-primary)"></i><?= e($title) ?></div>
                <table class="table table-sm">
                    <thead><tr><th class="ps-3"><?= e($col) ?></th><th class="text-end pe-3">Jumlah</th></tr></thead>
                    <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr><td class="ps-3"><?= e($r['name']) ?></td><td class="text-end pe-3 fw-semibold"><?= (int) $r['n'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="p-3 pb-2 fw-bold"><i class="bi bi-calendar3 me-1" style="color: var(--lp-primary)"></i>Per bulan (laporan masuk)</div>
            <table class="table table-sm">
                <thead><tr><th class="ps-3">Bulan</th><th class="text-end pe-3">Jumlah</th></tr></thead>
                <tbody>
                <?php foreach ($byMonth as $m): ?>
                    <tr><td class="ps-3"><?= e($m['label']) ?></td><td class="text-end pe-3 fw-semibold"><?= $m['n'] ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../includes/layout_admin_bawah.php'; ?>
