<?php
/**
 * Koneksi database (PDO).
 *
 * Alur:
 * 1. Membaca kredensial dari includes/config.php (file terpisah, tidak di-commit).
 * 2. Membuat satu objek PDO dengan mode galat exception dan prepared statement asli.
 * 3. Jika koneksi gagal, pengguna hanya melihat pesan umum; detail teknis dicatat ke log server.
 */

function app_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $file = __DIR__ . '/config.php';
    if (!is_file($file)) {
        fatal_page(
            'Konfigurasi belum dibuat',
            'Salin <code>includes/config.example.php</code> menjadi <code>includes/config.php</code>, lalu muat ulang halaman ini.'
        );
    }
    $config = require $file;
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $c = app_config()['db'];
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);

    try {
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // galat SQL dilempar sebagai exception
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // hasil berupa array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statement asli dari server
        ]);
        // Samakan zona waktu database dengan aplikasi (WIB)
        $pdo->exec("SET time_zone = '+07:00'");
    } catch (PDOException $e) {
        error_log('[LaporPhish] Koneksi database gagal: ' . $e->getMessage());
        $detail = !empty(app_config()['app']['debug']) ? '<br><small>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small>' : '';
        fatal_page(
            'Database tidak dapat dihubungi',
            'Pastikan MySQL di XAMPP sudah berjalan dan database <code>laporphish</code> sudah diimpor dari <code>database/laporphish.sql</code>.' . $detail
        );
    }
    return $pdo;
}

/** Halaman galat sederhana yang tidak bergantung pada database. */
function fatal_page(string $title, string $messageHtml): never
{
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title></head>'
        . '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:4rem auto;padding:0 1rem;color:#231F40">'
        . '<h1 style="font-size:1.4rem">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>'
        . '<p>' . $messageHtml . '</p></body></html>';
    exit;
}
