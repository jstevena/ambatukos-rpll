<?php
declare(strict_types=1);

class TagihanRepository extends Repository
{
    /** Subquery: denda berjalan (item terpisah dari harga sewa pokok). */
    private const DENDA = '(SELECT COALESCE(SUM(d.jumlah), 0) FROM tagihan_denda d WHERE d.id_tagihan = t.id_tagihan)';
    private const JANJI_STATUS = '(SELECT j.status FROM janji_bayar j WHERE j.id_tagihan = t.id_tagihan ORDER BY j.id_janji DESC LIMIT 1)';
    private const JANJI_TGL = '(SELECT j.tanggal_janji FROM janji_bayar j WHERE j.id_tagihan = t.id_tagihan ORDER BY j.id_janji DESC LIMIT 1)';
    private const STATUS_BAYAR = '(SELECT pb.status FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1)';
    private const CATATAN_BAYAR = '(SELECT pb.catatan_admin FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1)';

    public function create(array $data): int
    {
        return $this->insert('tagihan', $data);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return Database::one('SELECT * FROM tagihan WHERE id_tagihan = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('tagihan', 'id_tagihan', $id, $data);
    }

    public function detail(int $id): ?array
    {
        return Database::one(
            'SELECT t.*, p.id_penyewa, p.id_kamar, p.status AS status_pemesanan, k.nomor_kamar, u.nama AS nama_penyewa,
                    ' . self::DENDA . ' AS denda, ' . self::JANJI_STATUS . ' AS janji_status, ' . self::JANJI_TGL . ' AS janji_tanggal
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
               JOIN pengguna u ON u.id_pengguna = p.id_penyewa
              WHERE t.id_tagihan = ?',
            [$id]
        );
    }

    public function byPemesanan(int $idPemesanan): array
    {
        return Database::all(
            'SELECT t.*, ' . self::STATUS_BAYAR . ' AS status_bayar, ' . self::CATATAN_BAYAR . ' AS catatan_bayar,
                    ' . self::DENDA . ' AS denda, ' . self::JANJI_STATUS . ' AS janji_status, ' . self::JANJI_TGL . ' AS janji_tanggal
               FROM tagihan t WHERE t.id_pemesanan = ? ORDER BY t.jatuh_tempo, t.id_tagihan',
            [$idPemesanan]
        );
    }

    public function findByJenis(int $idPemesanan, string $jenis): ?array
    {
        return Database::one('SELECT * FROM tagihan WHERE id_pemesanan = ? AND jenis = ? ORDER BY id_tagihan DESC LIMIT 1', [$idPemesanan, $jenis]);
    }

    public function exists(int $idPemesanan, string $jenis, ?int $periode): bool
    {
        return Database::val(
            'SELECT 1 FROM tagihan WHERE id_pemesanan = ? AND jenis = ? AND periode_ke <=> ? LIMIT 1',
            [$idPemesanan, $jenis, $periode]
        ) !== null;
    }

    public function maxPeriode(int $idPemesanan): int
    {
        $v = Database::val("SELECT MAX(periode_ke) FROM tagihan WHERE id_pemesanan = ? AND jenis IN ('Sewa Bulanan','Sewa Tahunan')", [$idPemesanan]);
        return $v === null ? 1 : max(1, (int)$v);
    }

    public function belumLunasByPemesanan(int $idPemesanan): int
    {
        return (int)Database::val(
            "SELECT COUNT(*) FROM tagihan WHERE id_pemesanan = ? AND status IN ('Belum Dibayar','Terlambat','Menunggu Verifikasi')",
            [$idPemesanan]
        );
    }

    /** Tagihan sewa paling lama menunggak pada satu pemesanan. */
    public function tertuaBelumLunasByPemesanan(int $idPemesanan): ?array
    {
        return Database::one(
            "SELECT t.* FROM tagihan t WHERE t.id_pemesanan = ? AND t.jenis IN ('Sewa Bulanan','Sewa Tahunan')
                AND t.status IN ('Belum Dibayar','Terlambat') ORDER BY t.jatuh_tempo LIMIT 1",
            [$idPemesanan]
        );
    }

    /** Riwayat tagihan penyewa (UC-05), per halaman. */
    public function paginateByPenyewa(int $idPenyewa, int $page, int $per): array
    {
        $total = (int)Database::val(
            'SELECT COUNT(*) FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan WHERE p.id_penyewa = ?',
            [$idPenyewa]
        );
        $offset = max(0, ($page - 1) * $per);
        $rows = Database::all(
            'SELECT t.*, k.nomor_kamar, ' . self::STATUS_BAYAR . ' AS status_bayar, ' . self::CATATAN_BAYAR . ' AS catatan_bayar,
                    ' . self::DENDA . ' AS denda, ' . self::JANJI_STATUS . ' AS janji_status, ' . self::JANJI_TGL . ' AS janji_tanggal
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE p.id_penyewa = ?
              ORDER BY t.jatuh_tempo DESC, t.id_tagihan DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset,
            [$idPenyewa]
        );
        return [$rows, $total];
    }

    public function berikutnyaByPenyewa(int $idPenyewa): ?array
    {
        return Database::one(
            "SELECT t.*, k.nomor_kamar, " . self::DENDA . " AS denda, " . self::JANJI_STATUS . " AS janji_status, " . self::JANJI_TGL . " AS janji_tanggal
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE p.id_penyewa = ? AND t.status IN ('Belum Dibayar','Terlambat','Menunggu Verifikasi')
              ORDER BY (t.status = 'Menunggu Verifikasi'), t.jatuh_tempo LIMIT 1",
            [$idPenyewa]
        );
    }

    /** UC-10: tagihan terbit yang belum dibayar pada pemesanan yang sedang berjalan. */
    public function belumBayar(string $now): array
    {
        return Database::all(
            "SELECT t.*, p.id_penyewa, p.id_pemesanan, k.nomor_kamar, u.nama AS nama_penyewa, u.no_hp,
                    GREATEST(0, DATEDIFF(?, DATE(t.jatuh_tempo))) AS hari_telat,
                    " . self::DENDA . " AS denda, " . self::JANJI_STATUS . " AS janji_status, " . self::JANJI_TGL . " AS janji_tanggal
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
               JOIN pengguna u ON u.id_pengguna = p.id_penyewa
              WHERE t.jenis <> 'DP' AND t.status IN ('Belum Dibayar','Terlambat')
                AND p.status IN ('Aktif','Menunggu Pelunasan') AND t.tanggal_terbit <= ?
              ORDER BY hari_telat DESC, t.jatuh_tempo",
            [$now, $now]
        );
    }

    public function jumlahPenyewaBelumBayar(string $now): int
    {
        return (int)Database::val(
            "SELECT COUNT(DISTINCT p.id_penyewa) FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE t.jenis <> 'DP' AND t.status IN ('Belum Dibayar','Terlambat')
                AND p.status IN ('Aktif','Menunggu Pelunasan') AND t.tanggal_terbit <= ?",
            [$now]
        );
    }

    /** Penyewa yang menunggak (>= $minHari hari lewat jatuh tempo). */
    public function menunggakByPenyewa(int $idPenyewa, string $today, int $minHari): ?array
    {
        return Database::one(
            "SELECT t.*, k.nomor_kamar, " . self::DENDA . " AS denda
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE p.id_penyewa = ? AND p.status = 'Aktif' AND t.jenis IN ('Sewa Bulanan','Sewa Tahunan')
                AND t.status IN ('Belum Dibayar','Terlambat') AND DATEDIFF(?, DATE(t.jatuh_tempo)) >= ?
              ORDER BY t.jatuh_tempo LIMIT 1",
            [$idPenyewa, $today, $minHari]
        );
    }

    public function jumlahPrioritas(string $today, int $minHari): int
    {
        return (int)Database::val(
            "SELECT COUNT(DISTINCT p.id_penyewa) FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE p.status = 'Aktif' AND t.jenis IN ('Sewa Bulanan','Sewa Tahunan')
                AND t.status IN ('Belum Dibayar','Terlambat') AND DATEDIFF(?, DATE(t.jatuh_tempo)) >= ?",
            [$today, $minHari]
        );
    }

    /** Pengingat sebelum jatuh tempo (H-3, H-1, hari H); tahap dicatat pada bitmask pengingat_terkirim. */
    public function untukPengingat(string $mulai, string $batas): array
    {
        return Database::all(
            "SELECT t.*, p.id_penyewa FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE t.jenis <> 'DP' AND t.status = 'Belum Dibayar' AND t.pengingat_terkirim < 7
                AND t.jatuh_tempo >= ? AND t.jatuh_tempo <= ?",
            [$mulai, $batas]
        );
    }

    /** Pengingat harian untuk tagihan sewa yang sudah lewat jatuh tempo. */
    public function untukPengingatTerlambat(string $today, string $now): array
    {
        return Database::all(
            "SELECT t.*, p.id_penyewa, k.nomor_kamar, " . self::DENDA . " AS denda
               FROM tagihan t
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE t.jenis IN ('Sewa Bulanan','Sewa Tahunan') AND t.status IN ('Belum Dibayar','Terlambat')
                AND p.status = 'Aktif' AND t.jatuh_tempo < ? AND (t.pengingat_terakhir IS NULL OR t.pengingat_terakhir < ?)",
            [$now, $today]
        );
    }

    public function untukTerlambat(string $now): array
    {
        return Database::all(
            "SELECT t.*, p.id_penyewa FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE t.jenis IN ('Sewa Bulanan','Sewa Tahunan') AND t.status = 'Belum Dibayar' AND t.jatuh_tempo < ?",
            [$now]
        );
    }

    // ---------------- Denda ----------------

    public function untukDenda(string $now): array
    {
        return Database::all(
            "SELECT t.* FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE t.jenis IN ('Sewa Bulanan','Sewa Tahunan') AND t.status IN ('Belum Dibayar','Terlambat')
                AND p.status = 'Aktif' AND t.jatuh_tempo < ?",
            [$now]
        );
    }

    public function dendaByTagihan(int $idTagihan): ?array
    {
        return Database::one('SELECT * FROM tagihan_denda WHERE id_tagihan = ?', [$idTagihan]);
    }

    public function dendaJumlah(int $idTagihan): float
    {
        return (float)(Database::val('SELECT jumlah FROM tagihan_denda WHERE id_tagihan = ?', [$idTagihan]) ?? 0);
    }

    public function buatDenda(int $idTagihan, string $terakhirDihitung): void
    {
        $this->insert('tagihan_denda', ['id_tagihan' => $idTagihan, 'jumlah' => 0, 'hari' => 0, 'terakhir_dihitung' => $terakhirDihitung]);
    }

    public function simpanDenda(int $idTagihan, float $jumlah, int $hari, string $terakhirDihitung): void
    {
        Database::exec(
            'UPDATE tagihan_denda SET jumlah = ?, hari = ?, terakhir_dihitung = ? WHERE id_tagihan = ?',
            [$jumlah, $hari, $terakhirDihitung, $idTagihan]
        );
    }

    public function setDendaTerakhir(int $idTagihan, string $tanggal): void
    {
        Database::exec('UPDATE tagihan_denda SET terakhir_dihitung = ? WHERE id_tagihan = ?', [$tanggal, $idTagihan]);
    }

    /** Tanggal janji bayar terakhir yang disetujui (denda dijeda sampai tanggal ini). */
    public function janjiDisetujuiSampai(int $idTagihan): ?string
    {
        $v = Database::val("SELECT MAX(tanggal_janji) FROM janji_bayar WHERE id_tagihan = ? AND status = 'Disetujui'", [$idTagihan]);
        return $v === null ? null : (string)$v;
    }
}
