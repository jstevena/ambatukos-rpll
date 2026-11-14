<?php
declare(strict_types=1);

class JanjiBayarRepository extends Repository
{
    public function create(array $data): int
    {
        return $this->insert('janji_bayar', $data);
    }

    public function find(int $id, bool $lock = false): ?array
    {
        return Database::one('SELECT * FROM janji_bayar WHERE id_janji = ?' . ($lock ? ' FOR UPDATE' : ''), [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('janji_bayar', 'id_janji', $id, $data);
    }

    /** Janji yang masih "hidup" (diajukan atau disetujui) untuk sebuah tagihan. */
    public function aktifByTagihan(int $idTagihan): ?array
    {
        return Database::one(
            "SELECT * FROM janji_bayar WHERE id_tagihan = ? AND status IN ('Diajukan','Disetujui') ORDER BY id_janji DESC LIMIT 1",
            [$idTagihan]
        );
    }

    public function menunggu(): array
    {
        return Database::all(
            "SELECT j.*, t.jenis, t.periode_ke, t.jumlah AS jumlah_tagihan, t.jatuh_tempo, p.id_penyewa, k.nomor_kamar,
                    u.nama AS nama_penyewa, u.no_hp,
                    (SELECT COALESCE(SUM(d.jumlah), 0) FROM tagihan_denda d WHERE d.id_tagihan = t.id_tagihan) AS denda
               FROM janji_bayar j
               JOIN tagihan t ON t.id_tagihan = j.id_tagihan
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
               JOIN kamar k ON k.id_kamar = p.id_kamar
               JOIN pengguna u ON u.id_pengguna = p.id_penyewa
              WHERE j.status = 'Diajukan' ORDER BY j.tanggal_ajukan"
        );
    }

    public function jumlahMenunggu(): int
    {
        return (int)Database::val("SELECT COUNT(*) FROM janji_bayar WHERE status = 'Diajukan'");
    }
}
