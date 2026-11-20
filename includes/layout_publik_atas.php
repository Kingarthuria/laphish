<?php
/**
 * Bagian atas halaman publik: <head>, navbar, dan pesan flash.
 * Variabel yang diharapkan: $pageTitle (string), $activeNav ('beranda'|'lapor'|'cek'|'lacak').
 */
$pageTitle = $pageTitle ?? 'LaporPhish';
$activeNav = $activeNav ?? '';
$navItems = [
    'beranda' => ['Beranda', 'index.php'],
    'lapor'   => ['Lapor', 'lapor.php'],
    'cek'     => ['Cek', 'cek.php'],
    'lacak'   => ['Lacak', 'lacak.php'],
];
$flashClass = ['sukses' => 'alert-ok', 'galat' => 'alert-err', 'info' => 'alert-info-lp', 'peringatan' => 'alert-warn'];
$flashIcon = ['sukses' => 'bi-check-circle-fill', 'galat' => 'bi-x-circle-fill', 'info' => 'bi-info-circle-fill', 'peringatan' => 'bi-exclamation-triangle-fill'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — LaporPhish</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="lp-public">
<nav class="navbar navbar-expand-md navbar-dark lp-nav px-3 px-md-4">
    <a class="navbar-brand" href="<?= url('index.php') ?>"><i class="bi bi-shield-check me-1"></i>LaporPhish</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublik" aria-controls="navPublik" aria-expanded="false" aria-label="Buka menu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPublik">
        <div class="navbar-nav ms-auto gap-md-2">
            <?php foreach ($navItems as $key => [$label, $href]): ?>
                <a class="nav-link<?= $key === $activeNav ? ' active' : '' ?>" href="<?= url($href) ?>"<?= $key === $activeNav ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
<main class="lp-page flex-grow-1">
    <?php foreach (take_flashes() as $f): ?>
        <div class="container pt-4" style="max-width: 820px">
            <div class="alert <?= $flashClass[$f['type']] ?? 'alert-info-lp' ?> mb-0" role="alert">
                <i class="bi <?= $flashIcon[$f['type']] ?? 'bi-info-circle-fill' ?> me-1"></i><?= e($f['message']) ?>
            </div>
        </div>
    <?php endforeach; ?>
