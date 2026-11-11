<?php
declare(strict_types=1);

class KamarRepository extends Repository
{
    private const FOTO_PERTAMA = '(SELECT f.path_file FROM kamar_foto f WHERE f.id_kamar = k.id_kamar ORDER BY f.urutan, f.id_foto LIMIT 1)';

    /** @return array{0: array, 1: int} baris dan total (dibatasi per halaman: NFR-01) */
    public function paginate(?string $status, int $page, int $per): array
    {
        $where = $status !== null ? 'WHERE k.status = ?' : '';
        $params = $status !== null ? [$status] : [];
        $total = (int)Database::val("SELECT COUNT(*) FROM kamar k $where", $params);
        $offset = max(0, ($page - 1) * $per);
        $rows = Database::all(
            'SELECT k.*, ' . self::FOTO_PERTAMA . " AS foto FROM kamar k $where ORDER BY k.nomor_kamar LIMIT " . (int)$per . ' OFFSET ' . (int)$offset,
            $params
        );
        return [$rows, $total];
    }

    public function all(): array
    {
        return Database::all('SELECT * FROM kamar ORDER BY nomor_kamar');
    }

    public function find(int $id): ?array
    {
        return Database::one('SELECT * FROM kamar WHERE id_kamar = ?', [$id]);
    }

    /** Penguncian baris kamar untuk mencegah pemesanan ganda (Tabel 5.14). Harus di dalam transaksi. */
    public function findForUpdate(int $id): ?array
    {
        return Database::one('SELECT * FROM kamar WHERE id_kamar = ? FOR UPDATE', [$id]);
    }

    public function byNomor(string $nomor): ?array
    {
        return Database::one('SELECT * FROM kamar WHERE nomor_kamar = ?', [$nomor]);
    }

    public function create(array $data): int
    {
        return $this->insert('kamar', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('kamar', 'id_kamar', $id, $data);
    }

    public function setStatus(int $id, string $status): void
    {
        Database::exec('UPDATE kamar SET status = ? WHERE id_kamar = ?', [$status, $id]);
    }

    public function delete(int $id): void
    {
        Database::exec('DELETE FROM kamar WHERE id_kamar = ?', [$id]);
    }

    public function countByStatus(): array
    {
        $out = ['Tersedia' => 0, 'Dipesan' => 0, 'Terisi' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS c FROM kamar GROUP BY status') as $r) {
            $out[$r['status']] = (int)$r['c'];
        }
        return $out;
    }

    // ---- foto ----
    public function fotos(int $idKamar): array
    {
        return Database::all('SELECT * FROM kamar_foto WHERE id_kamar = ? ORDER BY urutan, id_foto', [$idKamar]);
    }

    public function foto(int $idFoto): ?array
    {
        return Database::one('SELECT * FROM kamar_foto WHERE id_foto = ?', [$idFoto]);
    }

    public function addFoto(int $idKamar, string $path, int $urutan): void
    {
        $this->insert('kamar_foto', ['id_kamar' => $idKamar, 'path_file' => $path, 'urutan' => $urutan]);
    }

    public function deleteFoto(int $idFoto): void
    {
        Database::exec('DELETE FROM kamar_foto WHERE id_foto = ?', [$idFoto]);
    }

    public function deleteFotosByKamar(int $idKamar): void
    {
        Database::exec('DELETE FROM kamar_foto WHERE id_kamar = ?', [$idKamar]);
    }
}
