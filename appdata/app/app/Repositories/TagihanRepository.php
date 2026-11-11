<?php
declare(strict_types=1);

class TagihanRepository extends Repository
{
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
            'SELECT t.*, p.id_penyewa, p.id_kamar, p.status AS status_pemesanan, k.nomor_kamar, u.nama AS nama_penyewa
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
            'SELECT t.*, (SELECT pb.status FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1) AS status_bayar,
                    (SELECT pb.catatan_admin FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1) AS catatan_bayar
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

    /** Riwayat tagihan penyewa (UC-05), per halaman. */
    public function paginateByPenyewa(int $idPenyewa, int $page, int $per): array
    {
        $total = (int)Database::val(
            'SELECT COUNT(*) FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan WHERE p.id_penyewa = ?',
            [$idPenyewa]
        );
        $offset = max(0, ($page - 1) * $per);
        $rows = Database::all(
            'SELECT t.*, k.nomor_kamar,
                    (SELECT pb.status FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1) AS status_bayar,
                    (SELECT pb.catatan_admin FROM pembayaran pb WHERE pb.id_tagihan = t.id_tagihan ORDER BY pb.id_pembayaran DESC LIMIT 1) AS catatan_bayar
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
            "SELECT t.*, k.nomor_kamar FROM tagihan t
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
                    GREATEST(0, DATEDIFF(?, DATE(t.jatuh_tempo))) AS hari_telat
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

    public function untukPengingat(string $now, string $batas): array
    {
        return Database::all(
            "SELECT t.*, p.id_penyewa FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE t.jenis <> 'DP' AND t.status = 'Belum Dibayar' AND t.pengingat_terkirim = 0
                AND t.jatuh_tempo > ? AND t.jatuh_tempo <= ?",
            [$now, $batas]
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
}
