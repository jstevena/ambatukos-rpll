<?php
declare(strict_types=1);

const BULAN_ID = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

function env(string $key, ?string $default = null): ?string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

function e(mixed $v): string
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function rupiah(mixed $n): string
{
    return 'Rp' . number_format((float)$n, 0, ',', '.');
}

function tgl(?string $d, bool $jam = false): string
{
    if (!$d) {
        return '-';
    }
    $t = strtotime($d);
    if ($t === false) {
        return '-';
    }
    $s = date('j', $t) . ' ' . BULAN_ID[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $jam ? $s . ', ' . date('H:i', $t) : $s;
}

/** Badge status sesuai Tabel 5.24 (warna tidak hanya mengandalkan teks). */
function badge(string $status, ?string $force = null): string
{
    static $map = [
        'Tersedia' => 'ok', 'Lunas' => 'ok', 'Disetujui' => 'ok', 'Aktif' => 'ok', 'Dikembalikan' => 'ok', 'Diumumkan' => 'ok',
        'Dipesan' => 'warn', 'Belum Dibayar' => 'warn', 'Menunggu DP' => 'warn', 'Menunggu Pelunasan' => 'warn',
        'Menunggu Pengembalian' => 'warn', 'Baru' => 'warn',
        'Menunggu Verifikasi' => 'info', 'Diproses' => 'info', 'Ditetapkan' => 'info',
        'Terlambat' => 'danger', 'Ditolak' => 'danger', 'Hangus' => 'danger',
        'Terisi' => 'gray', 'Dibatalkan' => 'gray', 'Kedaluwarsa' => 'gray', 'Selesai' => 'gray', 'nonaktif' => 'gray',
    ];
    $cls = $force ?? ($map[$status] ?? 'gray');
    return '<span class="badge badge-' . $cls . '">' . e($status) . '</span>';
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function pull_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Nilai input lama (setelah validasi gagal). Dibersihkan oleh View::render. */
function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['_old'][$key] ?? $default;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function menu_for(?string $role): array
{
    return match ($role) {
        'penyewa' => [
            ['Beranda', '/beranda'], ['Kamar', '/kamar'], ['Pemesanan Saya', '/pemesanan'],
            ['Pembayaran', '/pembayaran'], ['Keluhan', '/keluhan'], ['Notifikasi', '/notifikasi'],
        ],
        'admin' => [
            ['Dashboard', '/dashboard'], ['Data Kamar', '/admin/kamar'], ['Verifikasi Pembayaran', '/admin/verifikasi'],
            ['Penyewa Belum Bayar', '/admin/belum-bayar'], ['Pengumuman Sewa', '/admin/pengumuman'],
            ['Keluhan', '/keluhan'], ['Pengguna', '/pengguna'],
        ],
        'pemilik' => [
            ['Dashboard', '/dashboard'], ['Kelola Kamar', '/admin/kamar'], ['Kenaikan Sewa', '/pemilik/kenaikan-sewa'], ['Keluhan', '/keluhan'], ['Pengguna', '/pengguna'], ['Laporan Pendapatan', '/laporan'],
        ],
        default => [['Daftar Kamar', '/kamar']],
    };
}

function pagination(int $page, int $pages): string
{
    if ($pages <= 1) {
        return '';
    }
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $out = '<nav class="pagination" aria-label="Halaman">';
    for ($i = 1; $i <= $pages; $i++) {
        $q = http_build_query(array_merge($_GET, ['page' => $i]));
        $out .= '<a class="' . ($i === $page ? 'active' : '') . '" href="' . e($path . '?' . $q) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}

function foto_url(?string $path): ?string
{
    return $path ? url('uploads/' . $path) : null;
}

function link_notifikasi(string $jenis, ?string $peran): string
{
    return match ($jenis) {
        'tagihan', 'pengingat' => '/pembayaran',
        'verifikasi' => $peran === 'admin' ? '/admin/verifikasi' : '/pembayaran',
        'pengembalian_dp' => $peran === 'admin' ? '/admin/verifikasi' : '/pemesanan',
        'kenaikan_sewa' => $peran === 'admin' ? '/admin/pengumuman' : '/beranda',
        'keluhan' => '/keluhan',
        default => '/notifikasi',
    };
}

/**
 * Normalisasi nomor HP Indonesia ke format +62.
 * Menerima 08xx..., 628xx..., atau +628xx... (spasi/strip diabaikan). Mengembalikan null bila tidak valid.
 */
function normalisasi_hp(?string $hp): ?string
{
    $s = preg_replace('/[\s().-]/', '', (string)$hp);
    if ($s === '') {
        return null;
    }
    if (str_starts_with($s, '+62')) {
        $n = $s;
    } elseif (str_starts_with($s, '62')) {
        $n = '+' . $s;
    } elseif (str_starts_with($s, '0')) {
        $n = '+62' . substr($s, 1);
    } else {
        return null;
    }
    return preg_match('/^\+628[0-9]{7,11}$/', $n) ? $n : null;
}

/** Nomor HP untuk ditampilkan: selalu +62 bila memungkinkan. */
function tampil_hp(?string $hp): string
{
    if ($hp === null || $hp === '') {
        return '-';
    }
    return normalisasi_hp($hp) ?? $hp;
}

/**
 * Normalisasi input nominal rupiah menjadi deret angka saja.
 * "Rp1.600.000", "1.600.000,00", "1600000" -> "1600000". String kosong bila tidak ada angka.
 */
function normalisasi_rupiah(?string $v): string
{
    $v = preg_replace('/,\d{1,2}$/', '', trim((string)$v)); // buang desimal (,00)
    return ltrim((string)preg_replace('/\D/', '', $v), '0');
}
