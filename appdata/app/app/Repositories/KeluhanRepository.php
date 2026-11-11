<?php
declare(strict_types=1);

class KeluhanRepository extends Repository
{
    public function create(array $data): int
    {
        return $this->insert('keluhan', $data);
    }

    public function find(int $id): ?array
    {
        return Database::one('SELECT * FROM keluhan WHERE id_keluhan = ?', [$id]);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('keluhan', 'id_keluhan', $id, $data);
    }

    private const SELECT = 'SELECT kl.*, k.nomor_kamar, u.nama AS nama_penyewa FROM keluhan kl
                              JOIN kamar k ON k.id_kamar = kl.id_kamar
                              JOIN pengguna u ON u.id_pengguna = kl.id_penyewa';

    public function semua(): array
    {
        return Database::all(self::SELECT . " ORDER BY (kl.status = 'Selesai'), kl.tanggal_lapor DESC");
    }

    public function byPenyewa(int $idPenyewa): array
    {
        return Database::all(self::SELECT . ' WHERE kl.id_penyewa = ? ORDER BY kl.tanggal_lapor DESC', [$idPenyewa]);
    }
}
