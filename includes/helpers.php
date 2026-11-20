<?php
/**
 * Fungsi bantu yang dipakai di semua halaman.
 */

/** Escape output HTML untuk mencegah XSS (NFR-02). */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Path relatif ke akar aplikasi, dari halaman publik ('') maupun admin ('../'). */
function url(string $path = ''): string
{
    return ROOT_REL . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/* ---------------- Pesan flash (tampil sekali setelah redirect) ---------------- */

/** @param string $type sukses | galat | info | peringatan */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ---------------- Indikator ---------------- */

const INDICATOR_TYPES = ['url' => 'URL', 'email' => 'Email', 'phone' => 'Nomor'];

/** Normalisasi nilai indikator sesuai docs/02a_desain_database.md bagian 8. */
function normalize_indicator(string $type, string $raw): string
{
    $raw = trim($raw);
    switch ($type) {
        case 'url':
            $v = preg_replace('#^https?://#i', '', $raw);
            $v = preg_replace('#^www\.#i', '', $v);
            $slash = strpos($v, '/');
            $host = $slash === false ? $v : substr($v, 0, $slash);
            $path = $slash === false ? '' : substr($v, $slash);
            return rtrim(strtolower($host) . $path, '/');
        case 'email':
            return strtolower($raw);
        case 'phone':
            $digits = preg_replace('/\D/', '', $raw);
            return str_starts_with($digits, '0') ? '62' . substr($digits, 1) : $digits;
    }
    return $raw;
}

/** Validasi nilai yang sudah dinormalisasi. Mengembalikan pesan galat atau null jika valid. */
function validate_indicator(string $type, string $normalized): ?string
{
    if ($normalized === '') {
        return 'Nilai indikator wajib diisi.';
    }
    if (mb_strlen($normalized) > 500) {
        return 'Nilai indikator terlalu panjang (maksimal 500 karakter).';
    }
    switch ($type) {
        case 'url':
            return preg_match('#^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+(:\d+)?(/\S*)?$#', $normalized)
                ? null : 'Format URL tidak dikenali. Contoh: https://contoh.example/login';
        case 'email':
            return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? null : 'Format email tidak valid.';
        case 'phone':
            return preg_match('/^62\d{7,13}$/', $normalized) ? null : 'Nomor telepon tidak valid. Contoh: 0812-3456-7890';
    }
    return 'Jenis indikator tidak dikenal.';
}

/** Tampilan "defanged": titik menjadi [.] agar tautan tidak bisa diklik (NFR-03). */
function defang(string $value): string
{
    $v = preg_replace('#^http#i', 'hxxp', $value);
    return str_replace('.', '[.]', $v);
}

/* ---------------- Kode tiket ---------------- */

/** Kode tiket acak LP-XXXXXXXX (tanpa huruf/angka yang mirip: 0, O, 1, I). */
function generate_ticket(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = 'LP-';
    foreach (str_split(random_bytes(8)) as $byte) {
        $code .= $alphabet[ord($byte) % 32];
    }
    return $code;
}

/* ---------------- Label dan warna status ---------------- */

const REPORT_STATUSES = [
    'baru'        => ['label' => 'Baru',        'class' => 'st-purple', 'icon' => 'bi-inbox'],
    'ditinjau'    => ['label' => 'Ditinjau',    'class' => 'st-orange', 'icon' => 'bi-hourglass-split'],
    'valid'       => ['label' => 'Valid',       'class' => 'st-red',    'icon' => 'bi-exclamation-octagon-fill'],
    'tidak_valid' => ['label' => 'Tidak valid', 'class' => 'st-grey',   'icon' => 'bi-dash-circle'],
];

const VERIFICATION_STATUSES = [
    'phishing'       => ['label' => 'Phishing',       'class' => 'st-red'],
    'belum'          => ['label' => 'Belum',          'class' => 'st-orange'],
    'bukan_phishing' => ['label' => 'Bukan phishing', 'class' => 'st-green'],
];

function status_badge(string $status): string
{
    $s = REPORT_STATUSES[$status] ?? ['label' => $status, 'class' => 'st-grey', 'icon' => 'bi-circle'];
    return '<span class="st ' . $s['class'] . '"><i class="bi ' . $s['icon'] . '"></i>' . e($s['label']) . '</span>';
}

function verification_badge(string $status): string
{
    $s = VERIFICATION_STATUSES[$status] ?? ['label' => $status, 'class' => 'st-grey'];
    return '<span class="st ' . $s['class'] . '">' . e($s['label']) . '</span>';
}

/* ---------------- Tanggal ---------------- */

function format_date(?string $value): string
{
    return $value ? date('d/m/Y', strtotime($value)) : '–';
}

function format_datetime(?string $value): string
{
    return $value ? date('d/m/Y H.i', strtotime($value)) : '–';
}
