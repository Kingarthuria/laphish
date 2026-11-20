<?php
/**
 * A1 — Login admin (FR-06)
 * Pesan galat sama untuk username maupun password yang salah,
 * agar tidak membocorkan username mana yang terdaftar.
 */
require __DIR__ . '/../includes/init.php';

if (is_admin()) {
    redirect('admin/index.php');
}

$username = '';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } elseif (attempt_login($username, $password)) {
        flash('sukses', 'Selamat datang, ' . current_admin()['name'] . '.');
        redirect('admin/index.php');
    } else {
        $error = 'Username atau password salah.';
    }
}
$flashes = take_flashes();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Admin LaporPhish</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body>
<div class="lp-login">
    <div class="card p-4" style="width: 100%; max-width: 380px">
        <div class="text-center mb-3">
            <div class="fw-bold fs-4"><i class="bi bi-shield-check me-1" style="color: var(--lp-primary)"></i>LaporPhish</div>
            <div class="text-muted-lp small">Masuk sebagai petugas unit TI</div>
        </div>
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-info-lp small py-2"><i class="bi bi-info-circle-fill me-1"></i><?= e($f['message']) ?></div>
        <?php endforeach; ?>
        <?php if ($error): ?>
            <div class="alert alert-err small py-2" role="alert"><i class="bi bi-x-circle-fill me-1"></i><?= e($error) ?></div>
        <?php endif; ?>
        <form method="post" action="<?= url('admin/login.php') ?>" novalidate>
            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span><input class="form-control" id="username" name="username" maxlength="50" value="<?= e($username) ?>" autocomplete="username" required autofocus></div>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span><input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required></div>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-box-arrow-in-right me-1"></i>Masuk</button>
        </form>
        <div class="text-center mt-3 small"><a href="<?= url('index.php') ?>" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a></div>
    </div>
</div>
</body>
</html>
