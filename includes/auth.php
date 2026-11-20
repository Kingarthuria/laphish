<?php
/**
 * Autentikasi admin berbasis session (FR-06, NFR-04).
 */

function is_admin(): bool
{
    return !empty($_SESSION['admin_id']);
}

/** Dipanggil di awal setiap halaman admin. Pengunjung yang belum login diarahkan ke halaman login. */
function require_admin(): void
{
    if (!is_admin()) {
        flash('info', 'Silakan masuk terlebih dahulu.');
        redirect('admin/login.php');
    }
}

function current_admin(): array
{
    return [
        'id'   => (int) ($_SESSION['admin_id'] ?? 0),
        'name' => (string) ($_SESSION['admin_name'] ?? ''),
    ];
}

/** Mencoba login. Pesan galat sama untuk username dan password yang salah. */
function attempt_login(string $username, string $password): bool
{
    $stmt = db()->prepare('SELECT admin_id, password_hash, full_name FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        return false;
    }

    session_regenerate_id(true); // cegah session fixation
    $_SESSION['admin_id'] = (int) $admin['admin_id'];
    $_SESSION['admin_name'] = $admin['full_name'];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
