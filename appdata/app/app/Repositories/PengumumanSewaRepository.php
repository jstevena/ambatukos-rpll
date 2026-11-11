<?php
declare(strict_types=1);

class PengumumanSewaRepository extends Repository
{
    public function create(array $data): int
    {
        return $this->insert('pengumuman_sewa', $data);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return Database::one('SELECT * FROM pengumuman_sewa WHERE id_pengumuman = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('pengumuman_sewa', 'id_pengumuman', $id, $data);
    }

    public function daftar(): array
    {
        return Database::all(
            'SELECT s.*, k.nomor_kamar, k.biaya_per_bulan, po.nama AS nama_pemilik
               FROM pengumuman_sewa s
               JOIN kamar k ON k.id_kamar = s.id_kamar
               JOIN pengguna po ON po.id_pengguna = s.id_pemilik
              ORDER BY (s.status = \'Ditetapkan\') DESC, s.tanggal_berlaku DESC, s.id_pengumuman DESC'
        );
    }

    public function jumlahDitetapkan(): int
    {
        return (int)Database::val("SELECT COUNT(*) FROM pengumuman_sewa WHERE status = 'Ditetapkan'");
    }

    /** Biaya baru terakhir yang sudah diumumkan dan berlaku pada tanggal tertentu (BR-12). */
    public function biayaBerlaku(int $idKamar, string $tanggal): ?float
    {
        $v = Database::val(
            "SELECT biaya_baru FROM pengumuman_sewa
              WHERE id_kamar = ? AND status = 'Diumumkan' AND tanggal_berlaku <= ?
              ORDER BY tanggal_berlaku DESC, id_pengumuman DESC LIMIT 1",
            [$idKamar, $tanggal]
        );
        return $v === null ? null : (float)$v;
    }

    /** Kenaikan yang sudah diumumkan tetapi belum berlaku (info di detail kamar). */
    public function mendatang(int $idKamar, string $hariIni): ?array
    {
        return Database::one(
            "SELECT * FROM pengumuman_sewa WHERE id_kamar = ? AND status = 'Diumumkan' AND tanggal_berlaku > ?
              ORDER BY tanggal_berlaku LIMIT 1",
            [$idKamar, $hariIni]
        );
    }
}
