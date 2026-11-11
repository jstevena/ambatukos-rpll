<?php
declare(strict_types=1);

class PembayaranRepository extends Repository
{
    public function create(array $data): int
    {
        return $this->insert('pembayaran', $data);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return Database::one('SELECT * FROM pembayaran WHERE id_pembayaran = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('pembayaran', 'id_pembayaran', $id, $data);
    }

    private const DETAIL = 'SELECT pb.*, t.jenis, t.periode_ke, t.jumlah AS jumlah_tagihan, t.jatuh_tempo, t.id_pemesanan,
                                   p.id_penyewa, p.status AS status_pemesanan, p.alasan_batal, k.nomor_kamar, u.nama AS nama_penyewa, u.no_hp
                              FROM pembayaran pb
                              JOIN tagihan t ON t.id_tagihan = pb.id_tagihan
                              JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
                              JOIN kamar k ON k.id_kamar = p.id_kamar
                              JOIN pengguna u ON u.id_pengguna = p.id_penyewa';

    public function detail(int $id): ?array
    {
        return Database::one(self::DETAIL . ' WHERE pb.id_pembayaran = ?', [$id]);
    }

    public function menungguVerifikasi(int $limit = 200): array
    {
        return Database::all(self::DETAIL . " WHERE pb.status = 'Menunggu Verifikasi' ORDER BY pb.tanggal_bayar LIMIT " . (int)$limit);
    }

    public function menungguPengembalian(): array
    {
        return Database::all(self::DETAIL . " WHERE pb.status = 'Menunggu Pengembalian' ORDER BY pb.tanggal_bayar");
    }

    public function jumlahMenungguVerifikasi(): int
    {
        return (int)Database::val("SELECT COUNT(*) FROM pembayaran WHERE status = 'Menunggu Verifikasi'");
    }

    /** Pembayaran DP yang masih "hidup" (bukan yang ditolak) untuk sebuah pemesanan. */
    public function dpAktif(int $idPemesanan): ?array
    {
        return Database::one(
            "SELECT pb.* FROM pembayaran pb JOIN tagihan t ON t.id_tagihan = pb.id_tagihan
              WHERE t.id_pemesanan = ? AND t.jenis = 'DP' AND pb.status <> 'Ditolak'
              ORDER BY pb.id_pembayaran DESC LIMIT 1",
            [$idPemesanan]
        );
    }

    /** Rekap pendapatan: pembayaran Disetujui pada bulan tertentu (UC-12). */
    public function disetujuiBulan(string $awal, string $akhir): array
    {
        return Database::all(
            "SELECT pb.*, t.jenis, t.periode_ke, k.nomor_kamar, u.nama AS nama_penyewa
               FROM pembayaran pb
               JOIN tagihan t ON t.id_tagihan = pb.id_tagihan
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
               JOIN pengguna u ON u.id_pengguna = p.id_penyewa
              WHERE pb.status = 'Disetujui' AND pb.tanggal_verifikasi >= ? AND pb.tanggal_verifikasi < ?
              ORDER BY pb.tanggal_verifikasi",
            [$awal, $akhir]
        );
    }

    public function totalPerBulan(string $dari): array
    {
        $out = [];
        foreach (Database::all(
            "SELECT DATE_FORMAT(tanggal_verifikasi, '%Y-%m') AS bulan, SUM(jumlah) AS total
               FROM pembayaran WHERE status = 'Disetujui' AND tanggal_verifikasi >= ? GROUP BY bulan",
            [$dari]
        ) as $r) {
            $out[$r['bulan']] = (float)$r['total'];
        }
        return $out;
    }
}
