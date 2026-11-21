<?php
/**
 * Bagian atas halaman admin: <head>, sidebar, dan pesan flash.
 * Variabel yang diharapkan: $pageTitle (string), $activeMenu ('dashboard'|'laporan'|'indikator'|'kategori'|'kanal').
 * Halaman admin wajib memanggil require_admin() sebelum memuat layout ini.
 */
$pageTitle = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? '';
$menu = [
    'dashboard' => ['Dashboard', 'admin/index.php', 'bi-speedometer2'],
    'laporan'   => ['Laporan', 'admin/laporan.php', 'bi-inbox'],
    'indikator' => ['Indikator', 'admin/indikator.php', 'bi-link-45deg'],
    'kategori'  => ['Kategori', 'admin/kategori.php', 'bi-tags'],
    'kanal'     => ['Kanal', 'admin/kanal.php', 'bi-broadcast'],
];
$flashClass = ['sukses' => 'alert-ok', 'galat' => 'alert-err', 'info' => 'alert-info-lp', 'peringatan' => 'alert-warn'];
$flashIcon = ['sukses' => 'bi-check-circle-fill', 'galat' => 'bi-x-circle-fill', 'info' => 'bi-info-circle-fill', 'peringatan' => 'bi-exclamation-triangle-fill'];
$me = current_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — Admin LaPhish</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="lp-admin">
<div class="lp-shell">
    <nav class="lp-side" aria-label="Menu admin">
        <a class="brand" href="<?= url('admin/index.php') ?>"><i class="bi bi-shield-check me-1"></i>LaPhish</a>
        <?php foreach ($menu as $key => [$label, $href, $icon]): ?>
            <a class="<?= $key === $activeMenu ? 'active' : '' ?>" href="<?= url($href) ?>"<?= $key === $activeMenu ? ' aria-current="page"' : '' ?>><i class="bi <?= $icon ?>"></i><?= e($label) ?></a>
        <?php endforeach; ?>
        <div class="bottom">
            <div class="who"><?= e($me['name']) ?></div>
            <a href="<?= url('admin/logout.php') ?>"><i class="bi bi-box-arrow-left"></i>Keluar</a>
        </div>
    </nav>
    <main class="lp-main">
        <?php foreach (take_flashes() as $f): ?>
            <div class="alert <?= $flashClass[$f['type']] ?? 'alert-info-lp' ?> small py-2" role="alert">
                <i class="bi <?= $flashIcon[$f['type']] ?? 'bi-info-circle-fill' ?> me-1"></i><?= e($f['message']) ?>
            </div>
        <?php endforeach; ?>
