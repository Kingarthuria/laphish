<?php
/**
 * Bagian atas halaman admin: <head>, sidebar, dan pesan flash.
 * Variabel yang diharapkan: $pageTitle (string), $activeMenu ('dashboard'|'laporan'|'indikator'|'kategori'|'kanal').
 * Halaman admin wajib memanggil require_admin() sebelum memuat layout ini.
 */
$pageTitle = $pageTitle ?? 'Admin';
$activeMenu = $activeMenu ?? '';
$menu = [
    'dashboard' => ['Dashboard', 'admin/index.php'],
    'laporan'   => ['Laporan', 'admin/laporan.php'],
    'indikator' => ['Indikator', 'admin/indikator.php'],
    'kategori'  => ['Kategori', 'admin/kategori.php'],
    'kanal'     => ['Kanal', 'admin/kanal.php'],
];
$flashClass = ['sukses' => 'alert-ok', 'galat' => 'alert-err', 'info' => 'alert-info-lp', 'peringatan' => 'alert-warn'];
$me = current_admin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Admin LaPhish</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="lp-admin">
<div class="lp-shell">
    <nav class="lp-side" aria-label="Menu admin">
        <a class="brand" href="<?= url('admin/index.php') ?>">LaPhish</a>
        <?php foreach ($menu as $key => [$label, $href]): ?>
            <a class="<?= $key === $activeMenu ? 'active' : '' ?>" href="<?= url($href) ?>"<?= $key === $activeMenu ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <div class="bottom">
            <div class="who"><?= e($me['name']) ?></div>
            <a href="<?= url('admin/logout.php') ?>">Keluar</a>
        </div>
    </nav>
    <main class="lp-main">
        <?php foreach (take_flashes() as $f): ?>
            <div class="alert <?= $flashClass[$f['type']] ?? 'alert-info-lp' ?> small py-2" role="alert">
                <?= e($f['message']) ?>
            </div>
        <?php endforeach; ?>
