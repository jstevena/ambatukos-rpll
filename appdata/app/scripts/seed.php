<?php
declare(strict_types=1);
/** Mengisi akun awal dan data demo saat tabel pengguna masih kosong. Aman dijalankan berulang. */
require __DIR__ . '/../app/bootstrap.php';

$pdo = null;
for ($i = 0; $i < 40; $i++) {
    try {
        $pdo = Database::pdo();
        $pdo->query('SELECT 1 FROM pengguna LIMIT 1');
        break;
    } catch (Throwable $e) {
        Database::reset();
        $pdo = null;
        sleep(2);
    }
}
if ($pdo === null) {
    fwrite(STDERR, "[seed] database belum siap\n");
    exit(1);
}

$repo = new PenggunaRepository();
if ($repo->count() > 0) {
    echo "[seed] data sudah ada, dilewati\n";
    exit(0);
}

$now = date('Y-m-d H:i:s');
$buat = static fn(array $d) => $repo->create($d + ['status_akun' => 'aktif', 'created_at' => $now]);

$idPemilik = $buat([
    'nama' => 'Bu Sari', 'username' => 'pemilik', 'password_hash' => password_hash(env('SEED_PEMILIK_PASSWORD', 'pemilik123'), PASSWORD_DEFAULT),
    'no_hp' => '081200000001', 'peran' => 'pemilik',
]);
$buat([
    'nama' => 'Pak Dedi', 'username' => 'admin', 'password_hash' => password_hash(env('SEED_ADMIN_PASSWORD', 'admin123'), PASSWORD_DEFAULT),
    'no_hp' => '081200000002', 'peran' => 'admin',
]);
echo "[seed] akun pemilik & admin dibuat\n";

// SEED_PENYEWA & SEED_KAMAR mengatur data contoh secara terpisah.
// Jika belum diisi, nilainya mengikuti SEED_DEMO (kompatibel dengan .env lama).
$aktif = static fn(string $key): bool => strtolower((string)env($key, env('SEED_DEMO', 'true'))) === 'true';

if ($aktif('SEED_PENYEWA')) {
    $buat([
        'nama' => 'Andi Pratama', 'username' => 'andi', 'password_hash' => password_hash(env('SEED_PENYEWA_PASSWORD', 'penyewa123'), PASSWORD_DEFAULT),
        'no_hp' => '081200000003', 'nik' => '3273010101010001', 'alamat' => 'Jl. Contoh No. 1, Bandung', 'peran' => 'penyewa',
    ]);
    echo "[seed] penyewa demo (andi) dibuat\n";
}

if ($aktif('SEED_KAMAR')) {
    $kamar = new KamarRepository();
    $demo = [
        ['101', 9.00, 'Kasur, Lemari, Meja belajar, Kipas angin, Kamar mandi luar', 800000, 8800000, 'tetap'],
        ['102', 12.00, 'Kasur, Lemari, Meja belajar, AC, Kamar mandi dalam', 1200000, 13200000, 'tetap'],
        ['103', 12.00, 'Kasur, Lemari, Meja belajar, AC, Kamar mandi dalam, Water heater', 1500000, 16500000, 'tetap'],
        ['201', 9.00, 'Kasur, Lemari, Kipas angin, Kamar mandi luar', 750000, null, 'sementara'],
        ['202', 15.00, 'Kasur besar, Lemari, Meja kerja, AC, Kamar mandi dalam, Balkon', 1800000, 19800000, 'tetap'],
        ['203', 12.00, 'Kasur, Lemari, Meja belajar, AC, Kamar mandi dalam', 1250000, null, 'sementara'],
    ];
    foreach ($demo as [$no, $luas, $fas, $bulan, $tahun, $jenis]) {
        $kamar->create([
            'id_pemilik' => $idPemilik, 'nomor_kamar' => $no, 'luas' => $luas, 'fasilitas' => $fas,
            'biaya_per_bulan' => $bulan, 'biaya_per_tahun' => $tahun, 'jenis_sewa' => $jenis, 'status' => 'Tersedia',
        ]);
    }
    echo "[seed] 6 kamar contoh dibuat\n";
}
