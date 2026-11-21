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

/**
 * Memecah URL yang sudah dinormalisasi (tanpa skema) menjadi subdomain, domain pemilik, port, dan jalur.
 * Hanya untuk tampilan "bedah alamat" di halaman Cek; tidak dipakai untuk keputusan apa pun.
 * Domain = dua label terakhir, atau tiga untuk akhiran Indonesia bertingkat (kampus.ac.id).
 */
function url_segments(string $normalized): array
{
    $slash = strpos($normalized, '/');
    $host = $slash === false ? $normalized : substr($normalized, 0, $slash);
    $rest = $slash === false ? '' : substr($normalized, $slash);
    $port = '';
    if (preg_match('/:\d+$/', $host, $m)) {
        $port = $m[0];
        $host = substr($host, 0, -strlen($port));
    }
    $labels = explode('.', $host);
    $twoLevel = ['ac.id', 'co.id', 'go.id', 'or.id', 'sch.id', 'web.id', 'my.id', 'net.id', 'mil.id'];
    if (preg_match('/^\d+(\.\d+){3}$/', $host)) {
        $n = count($labels); // alamat berupa angka (IP): seluruhnya dianggap satu kesatuan
    } else {
        $n = count($labels) >= 3 && in_array(implode('.', array_slice($labels, -2)), $twoLevel, true) ? 3 : 2;
    }
    $n = min($n, count($labels));
    return [
        'sub'    => count($labels) > $n ? implode('.', array_slice($labels, 0, -$n)) . '.' : '',
        'domain' => implode('.', array_slice($labels, -$n)),
        'port'   => $port,
        'rest'   => $rest,
    ];
}

/* ---------------- Pembagian halaman (daftar admin) ---------------- */

const PER_HALAMAN = 10;

/**
 * Halaman yang diminta lewat ?halaman=, dibatasi ke rentang yang ada.
 * @return array{0:int,1:int,2:int} [halaman sekarang, jumlah halaman, offset baris]
 */
function halaman_sekarang(int $totalBaris): array
{
    $jumlah = max(1, (int) ceil($totalBaris / PER_HALAMAN));
    $halaman = min(max((int) ($_GET['halaman'] ?? 1), 1), $jumlah);
    return [$halaman, $jumlah, ($halaman - 1) * PER_HALAMAN];
}

/** Rentang baris yang tampil: "11 sampai 20", atau "31" jika hanya satu baris. */
function rentang_baris(int $offset, int $jumlahBaris): string
{
    return $jumlahBaris === 1 ? (string) ($offset + 1) : ($offset + 1) . ' sampai ' . ($offset + $jumlahBaris);
}

/**
 * Navigasi halaman: Sebelumnya, nomor halaman, Berikutnya. Filter yang aktif ikut terbawa di tautan.
 * Tidak menampilkan apa pun jika hanya ada satu halaman.
 */
function navigasi_halaman(string $path, array $filter, int $halaman, int $jumlah): string
{
    if ($jumlah <= 1) {
        return '';
    }
    $filter = array_filter($filter, fn($v) => $v !== '' && $v !== 0 && $v !== null);
    $tautan = function (int $n) use ($path, $filter): string {
        $q = $filter + ($n > 1 ? ['halaman' => $n] : []);
        return url($path . ($q ? '?' . http_build_query($q) : ''));
    };

    // Nomor yang ditampilkan: pertama, terakhir, dan satu di kiri-kanan halaman sekarang
    $nomor = array_unique(array_filter([1, $halaman - 1, $halaman, $halaman + 1, $jumlah], fn($n) => $n >= 1 && $n <= $jumlah));
    sort($nomor);

    $html = '<nav class="lp-pager" aria-label="Halaman daftar"><span class="info">Halaman ' . $halaman . ' dari ' . $jumlah . '</span><ul>';
    $html .= $halaman > 1
        ? '<li><a href="' . e($tautan($halaman - 1)) . '" rel="prev">Sebelumnya</a></li>'
        : '<li><span class="mati" aria-disabled="true">Sebelumnya</span></li>';
    $sebelum = 0;
    foreach ($nomor as $n) {
        if ($sebelum && $n > $sebelum + 1) {
            $html .= '<li><span class="mati" aria-hidden="true">...</span></li>';
        }
        $html .= $n === $halaman
            ? '<li><span class="sekarang" aria-current="page">' . $n . '</span></li>'
            : '<li><a href="' . e($tautan($n)) . '" aria-label="Halaman ' . $n . '">' . $n . '</a></li>';
        $sebelum = $n;
    }
    $html .= $halaman < $jumlah
        ? '<li><a href="' . e($tautan($halaman + 1)) . '" rel="next">Berikutnya</a></li>'
        : '<li><span class="mati" aria-disabled="true">Berikutnya</span></li>';
    return $html . '</ul></nav>';
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
    'baru'        => ['label' => 'Baru',        'class' => 'st-blue'],
    'ditinjau'    => ['label' => 'Ditinjau',    'class' => 'st-orange'],
    'valid'       => ['label' => 'Valid',       'class' => 'st-red'],
    'tidak_valid' => ['label' => 'Tidak valid', 'class' => 'st-grey'],
];

const VERIFICATION_STATUSES = [
    'phishing'       => ['label' => 'Phishing',       'class' => 'st-red'],
    'belum'          => ['label' => 'Belum',          'class' => 'st-orange'],
    'bukan_phishing' => ['label' => 'Bukan phishing', 'class' => 'st-green'],
];

function status_badge(string $status): string
{
    $s = REPORT_STATUSES[$status] ?? ['label' => $status, 'class' => 'st-grey'];
    return '<span class="st ' . $s['class'] . '">' . e($s['label']) . '</span>';
}

function verification_badge(string $status): string
{
    $s = VERIFICATION_STATUSES[$status] ?? ['label' => $status, 'class' => 'st-grey'];
    return '<span class="st ' . $s['class'] . '">' . e($s['label']) . '</span>';
}

/* ---------------- Tanggal ---------------- */

function format_date(?string $value): string
{
    return $value ? date('d/m/Y', strtotime($value)) : '';
}

function format_datetime(?string $value): string
{
    return $value ? date('d/m/Y H.i', strtotime($value)) : '';
}
