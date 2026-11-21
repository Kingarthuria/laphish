<?php
/**
 * Uji Black-Box otomatis lewat HTTP untuk LaPhish.
 *
 * Mencakup kasus di docs/04_pengujian.md yang bisa dijalankan tanpa peramban:
 * TC-01 sampai TC-10, TC-13 sampai TC-35, TC-37 sampai TC-56, TC-59 sampai TC-62, dan TC-68 sampai TC-73.
 * Kasus yang butuh peramban (TC-11, TC-12, TC-57, TC-58, TC-63 sampai TC-67) tidak ada di sini.
 *
 * Cara pakai (aplikasi sudah berjalan di XAMPP, database berisi data awal):
 *   php tests/uji_blackbox.php
 *   php tests/uji_blackbox.php http://127.0.0.1/laphish/    (alamat lain)
 * (php dari XAMPP: php.exe di folder php pada folder instalasi XAMPP)
 *
 * Peringatan:
 * - Skrip ini MENULIS data uji ke database (laporan, indikator, kategori, kanal). Pulihkan setelahnya
 *   dengan mengimpor ulang database/laphish.sql.
 * - TC-54 dan TC-55 mengubah includes/config.php sebentar lalu mengembalikannya. Jangan hentikan skrip di tengah.
 * - Akun admin demo diambil dari README.md. Hanya untuk dipakai di komputer sendiri (localhost).
 *
 * Kode keluar: 0 jika semua kasus lulus, 1 jika ada yang gagal.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Jalankan lewat baris perintah.\n");
}

$ROOT = dirname(__DIR__);
$BASE = rtrim($argv[1] ?? 'http://127.0.0.1/laphish/', '/') . '/';
$ADMIN = ['admin', 'LaPhish#Demo2026'];

if (!preg_match('#^http://(127\.0\.0\.1|localhost)(:\d+)?/#', $BASE)) {
    exit("Alamat harus di komputer sendiri (127.0.0.1 atau localhost).\n");
}

/* ------------------------------------------------------------------ klien HTTP */
final class Klien
{
    private string $jar;

    public function __construct(private string $base)
    {
        $this->jar = tempnam(sys_get_temp_dir(), 'lp');
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    /** @return array{kode:int,url:string,isi:string} */
    public function minta(string $metode, string $path, array $data = []): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->base . ltrim($path, '/');
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar,
            CURLOPT_TIMEOUT => 30,
        ]);
        if ($metode === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        $isi = (string) curl_exec($ch);
        $hasil = ['kode' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'url' => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL), 'isi' => $isi];
        curl_close($ch);
        return $hasil;
    }

    public function get(string $path): array
    {
        return $this->minta('GET', $path);
    }

    public function post(string $path, array $data): array
    {
        return $this->minta('POST', $path, $data);
    }
}

/* ------------------------------------------------------------------ pelapor hasil */
$hasil = [];
function uji(string $id, string $judul, bool $lulus, string $catatan = ''): void
{
    global $hasil;
    $hasil[$id] = $lulus;
    printf("[%s] %-7s %s%s\n", $lulus ? 'LULUS' : 'GAGAL', $id, $judul, $lulus || $catatan === '' ? '' : "\n          -> $catatan");
}

function ada(string $isi, string $teks): bool
{
    return str_contains($isi, $teks);
}

function teks(string $html): string
{
    return trim(preg_replace('/\s+/', ' ', html_entity_decode(preg_replace('/<[^>]+>/', ' ', $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

function tiket_di(string $isi): array
{
    preg_match_all('/LP-[A-HJ-NP-Z2-9]{8}/', $isi, $m);
    return array_values(array_unique($m[0]));
}

/* ------------------------------------------------------------------ database (hanya untuk memeriksa hasil) */
$cfg = require $ROOT . '/includes/config.php';
$c = $cfg['db'];
$pdo = new PDO(sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']), $c['user'], $c['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$satu = function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetchColumn();
};
$baris = function (string $sql, array $p = []) use ($pdo) {
    $s = $pdo->prepare($sql);
    $s->execute($p);
    return $s->fetch() ?: null;
};
$hitung = fn(string $tabel): int => (int) $satu("SELECT COUNT(*) FROM $tabel");

$kat = array_column($pdo->query('SELECT name, category_id FROM categories')->fetchAll(), 'category_id', 'name');
$kan = array_column($pdo->query('SELECT name, channel_id FROM channels')->fetchAll(), 'channel_id', 'name');
$idLaporan = fn(string $tiket): int => (int) $satu('SELECT report_id FROM reports WHERE ticket_code = ?', [$tiket]);

function bentuk_lapor(array $o = []): array
{
    global $kat, $kan;
    return array_merge([
        'action' => 'kirim', 'channel_id' => $kan['WhatsApp'], 'incident_date' => '2026-11-19', 'category_id' => $kat['Pencurian akun'],
        'chronology' => 'Menerima pesan WhatsApp berisi tautan palsu untuk keperluan uji.',
        'ind_type' => ['url'], 'ind_value' => ['bb-uji-satu.example/a'], 'name' => '', 'email' => '',
    ], $o);
}

/* ------------------------------------------------------------------ sesi */
$anon = new Klien($BASE);
$admin = new Klien($BASE);
$r = $admin->get('admin/login.php');
$r = $admin->post('admin/login.php', ['username' => $ADMIN[0], 'password' => $ADMIN[1]]);
$masuk = str_contains($r['url'], 'admin/index.php') && ada($r['isi'], 'Selamat datang');

echo "== Uji Black-Box LaPhish ($BASE)\n";
$awal = $hitung('reports') === 42 && $hitung('indicators') === 31 && $hitung('reporters') === 16;
if (!$awal) {
    echo "Catatan: database bukan data awal (42 laporan, 31 indikator, 16 pelapor). Kasus yang bergantung pada data awal akan dilewati.\n";
}
uji('TC-30', 'Login dengan akun demo', $masuk, 'url=' . $r['url']);
if (!$masuk) {
    exit("Login admin gagal, uji dihentikan.\n");
}

/* ================================================================== data awal (hanya baca) */
if ($awal) {
    // TC-59 daftar laporan dibagi halaman
    $jumlah = [];
    $semua = [];
    for ($h = 1; $h <= 5; $h++) {
        $t = tiket_di($admin->get("admin/laporan.php?halaman=$h")['isi']);
        $jumlah[] = count($t);
        $semua = array_merge($semua, $t);
    }
    $h1 = $admin->get('admin/laporan.php?halaman=1')['isi'];
    uji('TC-59', 'Daftar laporan dibagi halaman', $jumlah === [10, 10, 10, 10, 2] && count(array_unique($semua)) === 42 && ada(teks($h1), 'Laporan 1 sampai 10 dari 42') && ada(teks($h1), 'Halaman 1 dari 5'), json_encode($jumlah));

    // TC-60 daftar indikator dibagi halaman
    $jumlah = [];
    for ($h = 1; $h <= 4; $h++) {
        $jumlah[] = substr_count($admin->get("admin/indikator.php?halaman=$h")['isi'], 'data-confirm="Hapus indikator');
    }
    uji('TC-60', 'Daftar indikator dibagi halaman', $jumlah === [10, 10, 10, 1], json_encode($jumlah));

    // TC-61 nomor halaman di luar rentang
    $ok = ada(teks($admin->get('admin/laporan.php?halaman=99')['isi']), 'Halaman 5 dari 5');
    foreach (['0', '-3', 'abc'] as $x) {
        $ok = $ok && ada(teks($admin->get('admin/laporan.php?halaman=' . $x)['isi']), 'Halaman 1 dari 5');
    }
    uji('TC-61', 'Nomor halaman di luar rentang atau tidak valid', $ok);

    // TC-62 filter terbawa
    $v = $admin->get('admin/laporan.php?status=valid')['isi'];
    $tv = $admin->get('admin/laporan.php?status=tidak_valid')['isi'];
    $kosong = $admin->get('admin/laporan.php?q=zzzzzzzz')['isi'];
    uji('TC-62', 'Filter terbawa dan jumlah halaman mengikuti hasil',
        ada(teks($v), 'Halaman 1 dari 4') && ada($v, 'status=valid&amp;halaman=2') && count(tiket_di($tv)) === 4 && !ada($tv, 'Berikutnya')
        && count(tiket_di($kosong)) === 0);

    // TC-32 angka dashboard sesuai database (dibaca sebelum data uji ditambahkan)
    $d = teks($admin->get('admin/index.php')['isi']);
    $tot = $hitung('reports');
    $st = array_column($pdo->query("SELECT status, COUNT(*) n FROM v_report_status GROUP BY status")->fetchAll(), 'n', 'status');
    $cocok = ada($d, "Total laporan $tot");
    foreach (['baru' => 'Baru', 'ditinjau' => 'Ditinjau', 'valid' => 'Valid', 'tidak_valid' => 'Tidak valid'] as $k => $lbl) {
        $cocok = $cocok && ada($d, $lbl . ' ' . ($st[$k] ?? 0));
    }
    uji('TC-32', 'Angka di dashboard sesuai database', $cocok, $d);
} else {
    foreach (['TC-32', 'TC-59', 'TC-60', 'TC-61', 'TC-62'] as $id) {
        echo "[LEWAT] $id (butuh data awal)\n";
    }
}

/* ================================================================== halaman publik */
$r = $anon->get('index.php');
uji('TC-01', 'Membuka beranda', $r['kode'] === 200 && ada($r['isi'], 'Cek') && ada($r['isi'], 'Lapor') && ada($r['isi'], 'Lacak') && ada($r['isi'], 'bedahInput'));

// TC-02 laporan tanpa identitas
$n0 = $hitung('reports');
$r = $anon->post('lapor.php', bentuk_lapor());
$T02 = tiket_di($r['isi'])[0] ?? '';
$id02 = $T02 ? $idLaporan($T02) : 0;
$ok = str_contains($r['url'], 'terkirim.php') && ada($r['isi'], 'Laporan berhasil dikirim') && $hitung('reports') === $n0 + 1 && $id02 > 0
    && $satu('SELECT reporter_id FROM reports WHERE report_id = ?', [$id02]) === null
    && (int) $satu('SELECT COUNT(*) FROM status_histories WHERE report_id = ? AND status = ?', [$id02, 'baru']) === 1
    && (int) $satu('SELECT COUNT(*) FROM report_indicators WHERE report_id = ?', [$id02]) === 1;
uji('TC-02', 'Mengirim laporan tanpa identitas', $ok, 'url=' . $r['url']);

// TC-03 pelapor dengan email yang sudah ada
$np = $hitung('reporters');
$idLama = (int) $satu('SELECT reporter_id FROM reporters WHERE email = ?', ['putri@kampus.example']);
$namaLama = (string) $satu('SELECT name FROM reporters WHERE reporter_id = ?', [$idLama]);
$r = $anon->post('lapor.php', bentuk_lapor(['name' => 'Nama Lain', 'email' => 'PUTRI@KAMPUS.EXAMPLE', 'ind_value' => ['bb-uji-dua.example/b']]));
$T03 = tiket_di($r['isi'])[0] ?? '';
$id03 = $T03 ? $idLaporan($T03) : 0;
$ok = $id03 > 0 && $hitung('reporters') === $np && (int) $satu('SELECT reporter_id FROM reports WHERE report_id = ?', [$id03]) === $idLama
    && $satu('SELECT name FROM reporters WHERE reporter_id = ?', [$idLama]) === $namaLama;
uji('TC-03', 'Pelapor dengan email yang sudah pernah dipakai', $ok);

// TC-04 formulir kosong
$n0 = $hitung('reports');
$r = $anon->post('lapor.php', ['action' => 'kirim', 'channel_id' => '', 'incident_date' => '', 'category_id' => '', 'chronology' => '', 'ind_type' => ['url'], 'ind_value' => [''], 'name' => '', 'email' => '']);
uji('TC-04', 'Mengirim formulir kosong', ada($r['isi'], 'Laporan belum bisa dikirim') && ada($r['isi'], 'Pilih kanal.') && $hitung('reports') === $n0);

// TC-05 .. TC-10 validasi
$r = $anon->post('lapor.php', bentuk_lapor(['incident_date' => date('Y-m-d', strtotime('+1 day'))]));
uji('TC-05', 'Tanggal kejadian di masa depan', ada($r['isi'], 'tidak boleh di masa depan') && $hitung('reports') === $n0);
$r = $anon->post('lapor.php', bentuk_lapor(['chronology' => 'pendek']));
uji('TC-06', 'Kronologi terlalu pendek', ada($r['isi'], 'minimal 10 karakter'));
$r = $anon->post('lapor.php', bentuk_lapor(['ind_value' => ['bukan url']]));
uji('TC-07', 'Format URL salah', ada($r['isi'], 'Indikator 1:'));
$r = $anon->post('lapor.php', bentuk_lapor(['ind_type' => array_fill(0, 6, 'url'), 'ind_value' => ['bb-banyak1.example/a', 'bb-banyak2.example/a', 'bb-banyak3.example/a', 'bb-banyak4.example/a', 'bb-banyak5.example/a', 'bb-banyak6.example/a']]));
uji('TC-08', 'Lebih dari 5 indikator dikirim langsung ke server', ada($r['isi'], 'Maksimal 5 indikator') && $hitung('reports') === $n0);
$ni = $hitung('indicators');
$r = $anon->post('lapor.php', bentuk_lapor(['ind_type' => ['url', 'url', 'phone'], 'ind_value' => ['HTTPS://WWW.BB-Uji-Tiga.example/x/', 'bb-uji-tiga.example/x', '0812-0000-1111']]));
$T09 = tiket_di($r['isi'])[0] ?? '';
$id09 = $T09 ? $idLaporan($T09) : 0;
uji('TC-09', 'Indikator ganda dalam satu laporan dan indikator yang sudah tercatat',
    $id09 > 0 && $hitung('indicators') === $ni + 1 && (int) $satu('SELECT COUNT(*) FROM report_indicators WHERE report_id = ?', [$id09]) === 2
    && (int) $satu("SELECT COUNT(*) FROM indicators WHERE type = 'url' AND value = 'bb-uji-tiga.example/x'") === 1);
$r = $anon->post('lapor.php', bentuk_lapor(['name' => 'Uji', 'email' => 'bukan-email']));
uji('TC-10', 'Format email pelapor salah', ada($r['isi'], 'Format email pelapor tidak valid'));

// TC-13 .. TC-14
uji('TC-13', 'Kode tiket setelah laporan terkirim', (bool) preg_match('/^LP-[A-HJ-NP-Z2-9]{8}$/', $T02) && $id02 > 0);
$baru = new Klien($BASE);
$r = $baru->get('terkirim.php');
uji('TC-14', 'Membuka halaman sukses tanpa mengirim laporan', str_contains($r['url'], 'lapor.php') && !ada($r['isi'], 'Laporan berhasil dikirim'));

// TC-15 .. TC-19 Lacak
$r = $anon->post('lacak.php', ['ticket' => $T02]);
uji('TC-15', 'Melacak laporan dengan kode tiket', ada($r['isi'], $T02) && ada($r['isi'], 'Laporan diterima') && ada($r['isi'], 'Baru'));
$r = $anon->post('lacak.php', ['ticket' => strtolower($T02)]);
uji('TC-16', 'Kode tiket ditulis huruf kecil', ada($r['isi'], 'Laporan diterima') && ada($r['isi'], $T02));
$r = $anon->post('lacak.php', ['ticket' => 'ABC']);
uji('TC-17', 'Format kode tiket salah', ada($r['isi'], 'Format kode tiket tidak sesuai'));
$r = $anon->post('lacak.php', ['ticket' => 'LP-ZZZZZZZZ']);
uji('TC-18', 'Kode tiket tidak terdaftar', ada($r['isi'], 'Kode tiket tidak ditemukan'));
$r = $anon->post('lacak.php', ['ticket' => $T03]);
uji('TC-19', 'Melacak laporan yang mencantumkan email pelapor', ada($r['isi'], $T03) && !ada(strtolower($r['isi']), 'putri@kampus.example'));

// TC-20 .. TC-27 Cek
$cek = fn(string $jenis, string $nilai) => $anon->post('cek.php', ['type' => $jenis, 'value' => $nilai]);
$r = $cek('url', 'HTTPS://WWW.bantuan-kuota-mahasiswa.example/form/');
uji('TC-20', 'Cek URL terverifikasi phishing yang ditulis berbeda', ada($r['isi'], 'Terverifikasi phishing') && ada($r['isi'], 'bantuan-kuota-mahasiswa[.]example'));
$phishingHtml = $r['isi'];
$r = $cek('url', 'bb-uji-satu.example/a');
uji('TC-21', 'Cek URL yang belum diverifikasi', ada($r['isi'], 'Tercatat, masih ditinjau'));
$amanUrl = (string) $satu("SELECT value FROM indicators WHERE type = 'url' AND verification_status = 'bukan_phishing' LIMIT 1");
$r = $cek('url', $amanUrl);
uji('TC-22', 'Cek URL yang sudah diperiksa dan aman', $amanUrl !== '' && ada($r['isi'], 'Sudah diperiksa, tidak terbukti phishing'));
$r = $cek('url', 'https://bb-belum-pernah.example/z');
$pre = $anon->post('lapor.php', ['action' => 'prefill', 'type' => 'url', 'value' => 'bb-belum-pernah.example/z']);
uji('TC-23', 'Cek URL yang belum pernah dilaporkan, lalu melaporkannya',
    ada($r['isi'], 'Belum ada data') && ada($r['isi'], 'Laporkan nilai ini') && ada($pre['isi'], 'bb-belum-pernah.example/z'));
$a = $cek('phone', '0812-0000-1111');
$b = $cek('phone', '+62 812-0000-1111');
$judul = fn(string $h) => preg_match('/class="res res-[a-z]+".*?class="t[^"]*">([^<]*)</s', $h, $m) ? trim($m[1]) : '';
uji('TC-24', 'Cek nomor dengan format penulisan berbeda', $judul($a['isi']) !== '' && $judul($a['isi']) === $judul($b['isi']) && $judul($a['isi']) !== 'Belum ada data', $judul($a['isi']) . ' | ' . $judul($b['isi']));
$r = $cek('email', 'bukan-email');
uji('TC-25', 'Cek email dengan format salah', ada($r['isi'], 'Format email tidak valid'));
$r = $cek('url', '<script>alert(1)</script>');
uji('TC-26', 'Memasukkan skrip ke kolom Cek', !ada($r['isi'], '<script>alert(1)</script>'));
uji('TC-27', 'Tampilan URL pada hasil Cek', substr_count($phishingHtml, 'href="http') === 0 && ada($phishingHtml, '[.]'));

/* ================================================================== admin */
// TC-28 .. TC-31
$r = $anon->get('admin/index.php');
uji('TC-28', 'Membuka halaman admin tanpa login', str_contains($r['url'], 'login.php') && ada($r['isi'], 'Silakan masuk terlebih dahulu'));
$r = $anon->post('admin/login.php', ['username' => 'admin', 'password' => 'salah-sekali']);
uji('TC-29', 'Login dengan password salah', ada($r['isi'], 'Username atau password salah.'));
$k2 = new Klien($BASE);
$k2->post('admin/login.php', ['username' => $ADMIN[0], 'password' => $ADMIN[1]]);
$r = $k2->get('admin/logout.php');
$r2 = $k2->get('admin/index.php');
uji('TC-31', 'Logout lalu membuka admin lagi', ada($r['isi'], 'Anda sudah keluar') && str_contains($r2['url'], 'login.php'));

// TC-33 .. TC-34
$r = $admin->get('admin/laporan.php?q=' . urlencode($T02));
uji('TC-33', 'Mencari laporan dengan kode tiket', tiket_di($r['isi']) === [$T02]);
$from = '2026-11-01';
$to = '2026-11-30';
$sql = "SELECT COUNT(*) FROM reports r JOIN v_report_status s ON s.report_id = r.report_id WHERE s.status = 'baru' AND r.category_id = ? AND DATE(r.created_at) BETWEEN ? AND ?";
$exp = (int) $satu($sql, [$kat['Pencurian akun'], $from, $to]);
$tot = 0;
$halaman = max(1, (int) ceil($exp / 10)); // nomor halaman di luar rentang dibatasi ke halaman terakhir, jadi jangan meminta lebih dari yang ada
for ($h = 1; $h <= $halaman; $h++) {
    $tot += count(tiket_di($admin->get("admin/laporan.php?status=baru&category_id={$kat['Pencurian akun']}&from=$from&to=$to&halaman=$h")['isi']));
}
uji('TC-34', 'Filter gabungan', $tot === $exp, "halaman=$tot, database=$exp");

// TC-35, TC-68, TC-69 edit laporan (hanya kategori dan kanal)
$sebelum = $baris('SELECT category_id, channel_id, incident_date, MD5(chronology) AS k FROM reports WHERE report_id = ?', [$id02]);
$r = $admin->get("admin/laporan_edit.php?id=$id02");
uji('TC-69', 'Halaman edit tidak memuat isian tanggal dan kronologi', !ada($r['isi'], 'name="chronology"') && !ada($r['isi'], 'name="incident_date"'));
$r = $admin->post("admin/laporan_edit.php?id=$id02", ['category_id' => $kat['Tagihan palsu'], 'channel_id' => $kan['SMS'], 'incident_date' => '2020-01-01', 'chronology' => 'DIUBAH OLEH ADMIN PALSU PALSU']);
$sesudah = $baris('SELECT category_id, channel_id, incident_date, MD5(chronology) AS k, updated_at FROM reports WHERE report_id = ?', [$id02]);
uji('TC-35', 'Mengubah kategori dan kanal laporan, tanggal dan kronologi tidak berubah',
    (int) $sesudah['category_id'] === $kat['Tagihan palsu'] && (int) $sesudah['channel_id'] === $kan['SMS']
    && $sesudah['incident_date'] === $sebelum['incident_date'] && $sesudah['k'] === $sebelum['k'] && $sesudah['updated_at'] !== null);
$r = $admin->post("admin/laporan_edit.php?id=$id02", ['category_id' => 9999, 'channel_id' => $kan['SMS']]);
uji('TC-68', 'Kategori tidak valid pada edit laporan', ada($r['isi'], 'Pilih kategori.') && (int) $satu('SELECT category_id FROM reports WHERE report_id = ?', [$id02]) === $kat['Tagihan palsu']);

// TC-38 .. TC-40, TC-44 status dan verifikasi (pada laporan TC-03)
$r = $admin->post("admin/laporan_detail.php?id=$id03", ['action' => 'status', 'status' => 'valid', 'note' => 'Terkonfirmasi phishing (uji).']);
$lacak = $anon->post('lacak.php', ['ticket' => $T03]);
uji('TC-38', 'Mengubah status dengan catatan',
    ada(teks($r['isi']), 'Status berhasil diubah menjadi "Valid"') && (int) $satu('SELECT COUNT(*) FROM status_histories WHERE report_id = ?', [$id03]) === 2
    && ada(teks($lacak['isi']), 'Terkonfirmasi phishing (uji).'));
$r = $admin->post("admin/laporan_detail.php?id=$id03", ['action' => 'status', 'status' => 'selesai', 'note' => '']);
uji('TC-39', 'Status di luar daftar dikirim langsung ke server', ada($r['isi'], 'Pilih status yang tersedia.') && (int) $satu('SELECT COUNT(*) FROM status_histories WHERE report_id = ?', [$id03]) === 2);
$ind = $baris('SELECT i.indicator_id, i.value FROM report_indicators ri JOIN indicators i ON i.indicator_id = ri.indicator_id WHERE ri.report_id = ?', [$id03]);
$r = $admin->post("admin/laporan_detail.php?id=$id03", ['action' => 'verify', 'indicator_id' => $ind['indicator_id'], 'verification_status' => 'phishing']);
$v = $baris('SELECT verification_status, verified_by, verified_at FROM indicators WHERE indicator_id = ?', [$ind['indicator_id']]);
$cekPhish = $cek('url', $ind['value']);
uji('TC-40', 'Verifikasi indikator dari detail laporan',
    $v['verification_status'] === 'phishing' && $v['verified_by'] !== null && $v['verified_at'] !== null && ada($cekPhish['isi'], 'Terverifikasi phishing'));
$r = $admin->post('admin/indikator.php', ['action' => 'delete', 'id' => $ind['indicator_id']]);
uji('TC-44', 'Menghapus indikator yang masih menjadi bukti laporan', ada($r['isi'], 'tidak bisa dihapus karena masih menjadi bukti') && (int) $satu('SELECT COUNT(*) FROM indicators WHERE indicator_id = ?', [$ind['indicator_id']]) === 1);

// TC-41 .. TC-43, TC-45, TC-53 indikator
$r = $admin->post('admin/indikator_form.php', ['type' => 'url', 'value' => 'HTTPS://www.BB-Admin.example/masuk/', 'verification_status' => 'phishing', 'note' => 'Sumber: uji']);
$idAdm = (int) $satu("SELECT indicator_id FROM indicators WHERE value = 'bb-admin.example/masuk'");
uji('TC-41', 'Menambah indikator tanpa laporan', $idAdm > 0 && ada($r['isi'], 'Indikator berhasil ditambahkan'));
$r = $admin->post('admin/indikator_form.php', ['type' => 'url', 'value' => 'bb-admin.example/masuk', 'verification_status' => 'phishing', 'note' => '']);
uji('TC-42', 'Menambah indikator yang sudah tercatat', ada($r['isi'], 'Indikator ini sudah tercatat.'));
$r = $admin->post("admin/indikator_form.php?id=$idAdm", ['verification_status' => 'belum', 'note' => 'Sumber: uji']);
$v = $baris('SELECT verification_status, verified_by FROM indicators WHERE indicator_id = ?', [$idAdm]);
uji('TC-43', 'Mengubah indikator kembali ke Belum diverifikasi', $v['verification_status'] === 'belum' && $v['verified_by'] === null);
$admin->get("admin/indikator.php?action=delete&id=$idAdm");
uji('TC-53', 'Menghapus lewat link GET', (int) $satu('SELECT COUNT(*) FROM indicators WHERE indicator_id = ?', [$idAdm]) === 1);
$r = $admin->post('admin/indikator.php', ['action' => 'delete', 'id' => $idAdm]);
uji('TC-45', 'Menghapus indikator yang tidak dipakai', (int) $satu('SELECT COUNT(*) FROM indicators WHERE indicator_id = ?', [$idAdm]) === 0);

// TC-37 hapus laporan (laporan TC-02)
$ni = $hitung('indicators');
$r = $admin->post("admin/laporan_detail.php?id=$id02", ['action' => 'delete']);
uji('TC-37', 'Menghapus laporan',
    ada($r['isi'], 'berhasil dihapus') && $idLaporan($T02) === 0
    && (int) $satu('SELECT COUNT(*) FROM report_indicators WHERE report_id = ?', [$id02]) === 0
    && (int) $satu('SELECT COUNT(*) FROM status_histories WHERE report_id = ?', [$id02]) === 0 && $hitung('indicators') === $ni);

// TC-46 .. TC-51 kategori dan kanal
$r = $admin->post('admin/kategori_form.php', ['name' => 'BB Kategori', 'description' => 'uji']);
$idK = (int) $satu("SELECT category_id FROM categories WHERE name = 'BB Kategori'");
$r2 = $admin->post("admin/kategori_form.php?id=$idK", ['name' => 'BB Kategori 2', 'description' => 'uji']);
uji('TC-46', 'Menambah dan mengubah kategori', $idK > 0 && ada($r['isi'], 'berhasil ditambahkan') && ada($r2['isi'], 'berhasil diperbarui') && $satu('SELECT name FROM categories WHERE category_id = ?', [$idK]) === 'BB Kategori 2');
$r = $admin->post('admin/kategori_form.php', ['name' => 'Pencurian akun', 'description' => '']);
uji('TC-47', 'Menambah kategori dengan nama yang sudah ada', ada($r['isi'], 'Nama kategori sudah ada.'));
$r = $admin->post('admin/kategori.php', ['action' => 'delete', 'id' => $kat['Pencurian akun']]);
uji('TC-48', 'Menghapus kategori yang masih dipakai', ada($r['isi'], 'tidak bisa dihapus karena masih dipakai') && (int) $satu('SELECT COUNT(*) FROM categories WHERE category_id = ?', [$kat['Pencurian akun']]) === 1);
$r = $admin->post('admin/kategori.php', ['action' => 'delete', 'id' => $idK]);
uji('TC-49', 'Menghapus kategori yang tidak dipakai', ada($r['isi'], 'berhasil dihapus') && (int) $satu('SELECT COUNT(*) FROM categories WHERE category_id = ?', [$idK]) === 0);

$a = $admin->post('admin/kanal_form.php', ['name' => 'BB Kanal']);
$idC = (int) $satu("SELECT channel_id FROM channels WHERE name = 'BB Kanal'");
$b = $admin->post("admin/kanal_form.php?id=$idC", ['name' => 'BB Kanal 2']);
$c2 = $admin->post('admin/kanal_form.php', ['name' => 'WhatsApp']);
$d = $admin->post('admin/kanal.php', ['action' => 'delete', 'id' => $idC]);
uji('TC-50', 'Menambah, mengubah, menambah nama ganda, dan menghapus kanal',
    $idC > 0 && ada($a['isi'], 'berhasil ditambahkan') && ada($b['isi'], 'berhasil diperbarui') && ada($c2['isi'], 'Nama kanal sudah ada.')
    && ada($d['isi'], 'berhasil dihapus') && (int) $satu('SELECT COUNT(*) FROM channels WHERE channel_id = ?', [$idC]) === 0);
$r = $admin->post('admin/kanal.php', ['action' => 'delete', 'id' => $kan['WhatsApp']]);
uji('TC-51', 'Menghapus kanal yang masih dipakai', ada($r['isi'], 'tidak bisa dihapus karena masih dipakai') && (int) $satu('SELECT COUNT(*) FROM channels WHERE channel_id = ?', [$kan['WhatsApp']]) === 1);

// TC-52 SQL injection di kolom cari
$r = $admin->get('admin/laporan.php?q=' . urlencode("' OR 1=1 -- "));
uji('TC-52', 'Mencoba SQL injection di kolom cari', $r['kode'] === 200 && count(tiket_di($r['isi'])) === 0 && !ada($r['isi'], 'SQLSTATE'));

/* ================================================================== lihat laporan di Lacak (TC-70 .. TC-73) */
$sampel = $awal ? 'LP-8ZJUVN8K' : (string) $satu('SELECT ticket_code FROM reports ORDER BY report_id LIMIT 1');
$idS = $idLaporan($sampel);
$kron = (string) $satu('SELECT chronology FROM reports WHERE report_id = ?', [$idS]);
$nInd = (int) $satu('SELECT COUNT(*) FROM report_indicators WHERE report_id = ?', [$idS]);
$r = $anon->post('lacak.php', ['ticket' => $sampel]);
$blok = preg_match('/<details.*?<\/details>/s', $r['isi'], $m) ? $m[0] : '';
uji('TC-70', 'Lacak menampilkan isi laporan', $blok !== '' && ada(teks($blok), $kron) && ada(teks($blok), "Indikator yang Anda kirim ($nInd)") && !ada($r['isi'], '<details open'));
$punyaPelapor = $baris('SELECT r.ticket_code, rp.name, rp.email FROM reports r JOIN reporters rp ON rp.reporter_id = r.reporter_id WHERE rp.email IS NOT NULL ORDER BY r.report_id LIMIT 1');
$r2 = $anon->post('lacak.php', ['ticket' => $punyaPelapor['ticket_code']]);
$blok2 = preg_match('/<details.*?<\/details>/s', $r['isi'], $mm) ? $mm[0] : '';
uji('TC-71', 'Nilai indikator di Lacak defanged dan identitas pelapor tidak tampil',
    !preg_match('#https?://#i', $blok) && ada($blok, '[.]') && !ada($r2['isi'], $punyaPelapor['email']) && !ada($r2['isi'], '>' . $punyaPelapor['name'] . '<'));
$r = $anon->post('lacak.php', ['ticket' => 'LP-ZZZZZZZZ']);
uji('TC-72', 'Kode tiket tidak ditemukan tidak menampilkan blok isi', !ada($r['isi'], '<details'));
$judulTerakhir = [];
foreach (['+62 0858-0000-9999', '0858-0000-9999', '+62 858 0000 9999', '0858-0000-9998'] as $v) {
    $judulTerakhir[] = $judul($cek('phone', $v)['isi']);
}
$tolak = true;
foreach (['812-3456-7890', '+1 202 555 0143', '12345', 'abc'] as $v) {
    $h = $cek('phone', $v)['isi'];
    $tolak = $tolak && (ada($h, 'Nomor telepon tidak valid') || ada($h, 'wajib diisi')) && $judul($h) === '';
}
uji('TC-73', 'Nomor telepon dengan berbagai format dan angka 0 berlebih',
    $judulTerakhir[0] !== '' && $judulTerakhir[0] !== 'Belum ada data' && count(array_unique(array_slice($judulTerakhir, 0, 3))) === 1 && $judulTerakhir[3] === 'Belum ada data' && $tolak,
    json_encode($judulTerakhir));

/* ================================================================== konfigurasi dan keamanan file */
foreach (['includes/config.php', 'includes/db.php', 'database/laphish.sql'] as $i => $f) {
    $kodeF[$f] = $anon->get($f)['kode'];
}
$kodeGit = $anon->get('.git/HEAD')['kode'];
uji('TC-56', 'Membuka file sensitif langsung dari peramban', array_values($kodeF) === [403, 403, 403] && $kodeGit !== 200, json_encode($kodeF) . " .git/HEAD=$kodeGit");

$cfgFile = $ROOT . '/includes/config.php';
$cadangan = file_get_contents($cfgFile);
$pulihkan = function () use ($cfgFile, $cadangan) {
    if (is_file($cfgFile . '.uji')) {
        @unlink($cfgFile . '.uji');
    }
    file_put_contents($cfgFile, $cadangan);
};
register_shutdown_function($pulihkan);
try {
    // TC-54 file konfigurasi belum dibuat
    unlink($cfgFile);
    $r = $anon->get('index.php');
    $pulihkan();
    uji('TC-54', 'File konfigurasi belum dibuat', $r['kode'] === 500 && ada($r['isi'], 'Konfigurasi belum dibuat') && !ada($r['isi'], 'Fatal error'));

    // TC-55 database tidak bisa dihubungi
    file_put_contents($cfgFile, preg_replace("/('name'\s*=>\s*)'[^']*'/", "$1'laphish_tidak_ada'", $cadangan, 1));
    $r = $anon->get('lapor.php');
    $pulihkan();
    uji('TC-55', 'Database tidak bisa dihubungi', ada($r['isi'], 'Database tidak dapat dihubungi') && !ada($r['isi'], 'SQLSTATE') && !ada($r['isi'], 'PDOException'), 'kode=' . $r['kode']);
} finally {
    $pulihkan();
}
$aman = file_get_contents($cfgFile) === $cadangan;
if (!$aman) {
    echo "PERINGATAN: includes/config.php tidak kembali seperti semula!\n";
}

/* ================================================================== ringkasan */
ksort($hasil, SORT_NATURAL);
$lulus = count(array_filter($hasil));
$gagal = array_keys(array_filter($hasil, fn($x) => !$x));
echo "\n== Ringkasan: $lulus dari " . count($hasil) . " kasus lulus.\n";
if ($gagal) {
    echo 'Gagal: ' . implode(', ', $gagal) . "\n";
}
echo "Pulihkan database: mysql -u root < database/laphish.sql\n";
exit($gagal ? 1 : 0);
