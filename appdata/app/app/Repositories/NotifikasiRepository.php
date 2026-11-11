<?php
declare(strict_types=1);

class NotifikasiRepository extends Repository
{
    public function create(int $idPengguna, string $jenis, string $pesan): void
    {
        $this->insert('notifikasi', [
            'id_pengguna' => $idPengguna,
            'jenis' => $jenis,
            'pesan' => mb_substr($pesan, 0, 255),
            'tanggal_kirim' => date('Y-m-d H:i:s'),
            'status_baca' => 0,
        ]);
    }

    public function daftar(int $idPengguna, int $page, int $per): array
    {
        $total = (int)Database::val('SELECT COUNT(*) FROM notifikasi WHERE id_pengguna = ?', [$idPengguna]);
        $offset = max(0, ($page - 1) * $per);
        $rows = Database::all(
            'SELECT * FROM notifikasi WHERE id_pengguna = ? ORDER BY id_notifikasi DESC LIMIT ' . (int)$per . ' OFFSET ' . (int)$offset,
            [$idPengguna]
        );
        return [$rows, $total];
    }

    public function terbaru(int $idPengguna, int $n): array
    {
        return Database::all('SELECT * FROM notifikasi WHERE id_pengguna = ? ORDER BY id_notifikasi DESC LIMIT ' . (int)$n, [$idPengguna]);
    }

    public function belumDibaca(int $idPengguna): int
    {
        return (int)Database::val('SELECT COUNT(*) FROM notifikasi WHERE id_pengguna = ? AND status_baca = 0', [$idPengguna]);
    }

    public function tandaiDibaca(int $idPengguna, int $id): void
    {
        Database::exec('UPDATE notifikasi SET status_baca = 1 WHERE id_notifikasi = ? AND id_pengguna = ?', [$id, $idPengguna]);
    }

    public function tandaiSemua(int $idPengguna): void
    {
        Database::exec('UPDATE notifikasi SET status_baca = 1 WHERE id_pengguna = ?', [$idPengguna]);
    }
}
