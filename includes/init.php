<?php
/**
 * Dimuat di baris pertama setiap halaman.
 * Menyiapkan zona waktu, session, path relatif, dan semua fungsi bantu.
 */

declare(strict_types=1);

require __DIR__ . '/db.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/auth.php';

date_default_timezone_set(app_config()['app']['timezone'] ?? 'Asia/Jakarta');

// Halaman di folder admin/ butuh '../' untuk menuju akar aplikasi.
// Cara ini membuat aplikasi tetap berjalan walaupun nama foldernya diganti.
define('ROOT_REL', basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'admin' ? '../' : '');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_name('laporphish');
    session_start();
}
