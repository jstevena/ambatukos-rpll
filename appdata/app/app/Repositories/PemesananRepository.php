<?php
declare(strict_types=1);

class PemesananRepository extends Repository
{
    public const STATUS_BERJALAN = "('Menunggu DP','Menunggu Pelunasan','Aktif')";

    public function create(array $data): int
    {
        return $this->insert('pemesanan', $data);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return Database::one('SELECT * FROM pemesanan WHERE id_pemesanan = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('pemesanan', 'id_pemesanan', $id, $data);
    }

    public function detail(int $id): ?array
    {
        return Database::one(
            'SELECT p.*, k.nomor_kamar, k.luas, k.biaya_per_bulan, u.nama AS nama_penyewa, u.no_hp
               FROM pemesanan p
               JOIN kamar k ON k.id_kamar = p.id_kamar
               JOIN pengguna u ON u.id_pengguna = p.id_penyewa
              WHERE p.id_pemesanan = ?',
            [$id]
        );
    }

    public function byPenyewa(int $idPenyewa): array
    {
        return Database::all(
            'SELECT p.*, k.nomor_kamar FROM pemesanan p JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE p.id_penyewa = ? ORDER BY p.tanggal_pesan DESC',
            [$idPenyewa]
        );
    }

    public function aktifByPenyewa(int $idPenyewa): array
    {
        return Database::all(
            "SELECT p.*, k.nomor_kamar FROM pemesanan p JOIN kamar k ON k.id_kamar = p.id_kamar
              WHERE p.id_penyewa = ? AND p.status = 'Aktif' ORDER BY p.tanggal_mulai_sewa",
            [$idPenyewa]
        );
    }

    public function berjalanByKamar(int $idKamar): ?array
    {
        return Database::one('SELECT * FROM pemesanan WHERE id_kamar = ? AND status IN ' . self::STATUS_BERJALAN . ' LIMIT 1', [$idKamar]);
    }

    public function aktifByKamar(int $idKamar): array
    {
        return Database::all("SELECT * FROM pemesanan WHERE id_kamar = ? AND status = 'Aktif'", [$idKamar]);
    }

    public function aktif(): array
    {
        return Database::all("SELECT * FROM pemesanan WHERE status = 'Aktif'");
    }

    /** Penyewa aktif per kamar (untuk tombol Akhiri Sewa di Kelola Kamar). */
    public function aktifIdPerKamar(): array
    {
        $map = [];
        foreach (Database::all("SELECT id_kamar, id_pemesanan FROM pemesanan WHERE status = 'Aktif'") as $r) {
            $map[(int)$r['id_kamar']] = (int)$r['id_pemesanan'];
        }
        return $map;
    }

    /** DP tidak dibayar dalam 24 jam (BR-01). Pemesanan dengan bukti yang menunggu verifikasi tidak ikut. */
    public function dpKedaluwarsa(string $now): array
    {
        return Database::all(
            "SELECT p.id_pemesanan FROM pemesanan p
               JOIN tagihan t ON t.id_pemesanan = p.id_pemesanan AND t.jenis = 'DP'
              WHERE p.status = 'Menunggu DP' AND t.status = 'Belum Dibayar' AND p.batas_bayar_dp < ?",
            [$now]
        );
    }

    /** Pelunasan tidak dibayar sampai tanggal masuk (BR-06). */
    public function pelunasanKedaluwarsa(string $now): array
    {
        return Database::all(
            "SELECT p.id_pemesanan FROM pemesanan p
               JOIN tagihan t ON t.id_pemesanan = p.id_pemesanan AND t.jenis = 'Pelunasan'
              WHERE p.status = 'Menunggu Pelunasan' AND t.status IN ('Belum Dibayar','Terlambat') AND t.jatuh_tempo < ?",
            [$now]
        );
    }

    public function aktifSementara(): array
    {
        return Database::all("SELECT * FROM pemesanan WHERE status = 'Aktif' AND jenis_sewa = 'sementara'");
    }
}
