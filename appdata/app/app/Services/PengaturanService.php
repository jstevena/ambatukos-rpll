<?php
declare(strict_types=1);

/** Pengaturan aplikasi (tabel pengaturan, kunci-nilai): fasilitas umum dan aturan denda. */
class PengaturanService
{
    private const DEFAULT = [
        'fasilitas_info' => 'Rumah kost baru, 2 lantai',
        'fasilitas_umum' => "# Kebersihan\n4 kamar mandi bersama (2 per lantai)\nDibersihkan setiap hari oleh petugas kebersihan (OB)\n# Dapur & makan\nKompor bersama\nPantri dan meja makan\n# Keamanan & parkir\nCCTV\nParkir motor\n# Utilitas & lingkungan\nWiFi\nSirkulasi udara dan pencahayaan baik",
        'harga_termasuk' => "Air PDAM gratis\nLPG gratis\nPetugas kebersihan (OB)",
        'harga_tidak_termasuk' => "Listrik (sistem token, dibeli sendiri)",
        'grace_hari' => '3',
        'menunggak_hari' => '8',
        'batas_putus_hari' => '14',
        'denda_tipe' => 'harian',
        'denda_nominal' => '10000',
        'denda_maks' => '200000',
    ];

    private static ?array $cache = null;

    public static function semua(): array
    {
        if (self::$cache === null) {
            self::$cache = self::DEFAULT;
            try {
                foreach (Database::all('SELECT kunci, nilai FROM pengaturan') as $r) {
                    self::$cache[$r['kunci']] = $r['nilai'];
                }
            } catch (Throwable $e) {
                error_log('[Pengaturan] ' . $e->getMessage()); // tabel belum dimigrasi: pakai nilai bawaan
            }
        }
        return self::$cache;
    }

    public static function get(string $kunci): string
    {
        return (string)(self::semua()[$kunci] ?? '');
    }

    public static function int(string $kunci): int
    {
        return (int)self::get($kunci);
    }

    /** Baris tidak kosong dari sebuah pengaturan teks. */
    public static function baris(string $kunci): array
    {
        $out = [];
        foreach (preg_split('/\R/', self::get($kunci)) ?: [] as $l) {
            $l = trim($l);
            if ($l !== '') {
                $out[] = $l;
            }
        }
        return $out;
    }

    /** Kelompok fasilitas: baris berawalan "#" menjadi judul kelompok. */
    public static function kelompok(string $kunci): array
    {
        $out = [];
        $cur = null;
        foreach (self::baris($kunci) as $l) {
            if ($l[0] === '#') {
                $out[] = ['judul' => trim(ltrim($l, '# ')), 'item' => []];
                $cur = count($out) - 1;
                continue;
            }
            if ($cur === null) {
                $out[] = ['judul' => '', 'item' => []];
                $cur = 0;
            }
            $out[$cur]['item'][] = $l;
        }
        return $out;
    }

    private static function simpan(array $kv): void
    {
        Database::transaction(function () use ($kv) {
            foreach ($kv as $k => $v) {
                if (array_key_exists($k, self::DEFAULT)) {
                    Database::exec(
                        'INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)',
                        [$k, (string)$v]
                    );
                }
            }
        });
        self::$cache = null;
    }

    /** Fasilitas umum: hanya pemilik (dibatasi di route). */
    public static function simpanFasilitas(array $in): void
    {
        $kv = [];
        foreach (['fasilitas_info' => 200, 'fasilitas_umum' => 3000, 'harga_termasuk' => 1000, 'harga_tidak_termasuk' => 1000] as $k => $maks) {
            $v = trim((string)($in[$k] ?? ''));
            if (mb_strlen($v) > $maks) {
                throw new AppException("Teks terlalu panjang (maksimal $maks karakter).");
            }
            $kv[$k] = $v;
        }
        self::simpan($kv);
    }

    public static function simpanDenda(array $in): void
    {
        $tipe = (string)($in['denda_tipe'] ?? '');
        if (!in_array($tipe, ['harian', 'flat'], true)) {
            throw new AppException('Pilih tipe denda.');
        }
        $nominal = normalisasi_rupiah((string)($in['denda_nominal'] ?? ''));
        $maks = normalisasi_rupiah((string)($in['denda_maks'] ?? ''));
        if ($nominal === '' || strlen($nominal) > 9) {
            throw new AppException('Nominal denda harus berupa angka lebih dari 0.');
        }
        if ($maks === '') {
            $maks = '0'; // 0 = tanpa batas maksimal
        }
        if (strlen($maks) > 10) {
            throw new AppException('Batas maksimal denda terlalu besar.');
        }
        if ($tipe === 'harian' && (float)$maks > 0 && (float)$maks < (float)$nominal) {
            throw new AppException('Batas maksimal denda tidak boleh lebih kecil dari denda per hari.');
        }
        $grace = (int)($in['grace_hari'] ?? -1);
        $tunggak = (int)($in['menunggak_hari'] ?? -1);
        $putus = (int)($in['batas_putus_hari'] ?? -1);
        if ($grace < 0 || $grace > 14) {
            throw new AppException('Masa tenggang harus 0 sampai 14 hari.');
        }
        if ($tunggak <= $grace || $tunggak > 60) {
            throw new AppException('Batas "Menunggak" harus lebih besar dari masa tenggang dan maksimal 60 hari.');
        }
        if ($putus <= $tunggak || $putus > 180) {
            throw new AppException('Batas putus sewa harus lebih besar dari batas "Menunggak" dan maksimal 180 hari.');
        }
        self::simpan([
            'denda_tipe' => $tipe, 'denda_nominal' => $nominal, 'denda_maks' => $maks,
            'grace_hari' => $grace, 'menunggak_hari' => $tunggak, 'batas_putus_hari' => $putus,
        ]);
    }
}
