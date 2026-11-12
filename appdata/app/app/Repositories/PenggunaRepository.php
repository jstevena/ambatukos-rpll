<?php
declare(strict_types=1);

class PenggunaRepository extends Repository
{
    public function byUsername(string $username): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE username = ?', [$username]);
    }

    public function byGoogleId(string $sub): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE google_id = ?', [$sub]);
    }

    public function byEmail(string $email): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE email = ?', [$email]);
    }

    public function byId(int $id): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE id_pengguna = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('pengguna', $data);
    }

    public function update(int $id, array $data): void
    {
        $this->updateById('pengguna', 'id_pengguna', $id, $data);
    }

    public function updatePassword(int $id, string $hash): void
    {
        Database::exec('UPDATE pengguna SET password_hash = ? WHERE id_pengguna = ?', [$hash, $id]);
    }

    public function idsByPeran(string $peran): array
    {
        $rows = Database::all("SELECT id_pengguna FROM pengguna WHERE peran = ? AND status_akun = 'aktif'", [$peran]);
        return array_map('intval', array_column($rows, 'id_pengguna'));
    }

    /**
     * Mantan penyewa: akun yang data pribadinya sudah dihapus, atau penyewa yang pernah menyewa sampai Selesai
     * dan kini tidak memiliki pemesanan berjalan.
     */
    private const MANTAN = "(LEFT(username, 9) = 'terhapus_' OR (
        EXISTS (SELECT 1 FROM pemesanan s WHERE s.id_penyewa = pengguna.id_pengguna AND s.status = 'Selesai')
        AND NOT EXISTS (SELECT 1 FROM pemesanan b WHERE b.id_penyewa = pengguna.id_pengguna AND b.status IN ('Menunggu DP','Menunggu Pelunasan','Aktif'))))";

    /** @return array{0: array, 1: int} $peran: penyewa | mantan | admin | pemilik | null (semua) */
    public function paginate(?string $peran, string $q, int $page, int $per): array
    {
        $where = [];
        $p = [];
        if ($peran === 'mantan') {
            $where[] = "peran = 'penyewa' AND " . self::MANTAN;
        } elseif ($peran === 'penyewa') {
            $where[] = "peran = 'penyewa' AND NOT " . self::MANTAN;
        } elseif ($peran !== null) {
            $where[] = 'peran = ?';
            $p[] = $peran;
        }
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(nama LIKE ? OR username LIKE ? OR no_hp LIKE ?)';
            array_push($p, $like, $like, $like);
        }
        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total = (int)Database::val("SELECT COUNT(*) FROM pengguna $w", $p);
        $offset = max(0, ($page - 1) * $per);
        $rows = Database::all(
            "SELECT id_pengguna, nama, username, no_hp, nik, peran, status_akun, created_at,
                    (peran = 'penyewa' AND " . self::MANTAN . ") AS mantan
               FROM pengguna $w
              ORDER BY FIELD(peran, 'pemilik', 'admin', 'penyewa'), nama LIMIT " . (int)$per . ' OFFSET ' . (int)$offset,
            $p
        );
        return [$rows, $total];
    }

    public function countByPeran(string $peran, bool $aktifSaja = false): int
    {
        return (int)Database::val(
            'SELECT COUNT(*) FROM pengguna WHERE peran = ?' . ($aktifSaja ? " AND status_akun = 'aktif'" : ''),
            [$peran]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        Database::exec('UPDATE pengguna SET status_akun = ? WHERE id_pengguna = ?', [$status, $id]);
    }

    /** Notifikasi milik pengguna ikut dihapus; data transaksi tetap menahan penghapusan (FK RESTRICT). */
    public function hapus(int $id): void
    {
        Database::exec('DELETE FROM notifikasi WHERE id_pengguna = ?', [$id]);
        Database::exec('DELETE FROM pengguna WHERE id_pengguna = ?', [$id]);
    }

    public function punyaPemesananBerjalan(int $id): bool
    {
        return Database::val(
            "SELECT 1 FROM pemesanan WHERE id_penyewa = ? AND status IN ('Menunggu DP','Menunggu Pelunasan','Aktif') LIMIT 1", [$id]
        ) !== null;
    }

    public function punyaTagihanBelumLunas(int $id): bool
    {
        return Database::val(
            "SELECT 1 FROM tagihan t JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE p.id_penyewa = ? AND t.status IN ('Belum Dibayar','Terlambat','Menunggu Verifikasi') LIMIT 1", [$id]
        ) !== null;
    }

    public function punyaPengembalianTertunda(int $id): bool
    {
        return Database::val(
            "SELECT 1 FROM pembayaran pb JOIN tagihan t ON t.id_tagihan = pb.id_tagihan
               JOIN pemesanan p ON p.id_pemesanan = t.id_pemesanan
              WHERE p.id_penyewa = ? AND pb.status = 'Menunggu Pengembalian' LIMIT 1", [$id]
        ) !== null;
    }

    /** Hapus data pribadi; baris dipertahankan agar riwayat transaksi tetap utuh. Akun tidak dapat dipakai login lagi. */
    public function anonimkan(int $id): void
    {
        Database::exec('DELETE FROM notifikasi WHERE id_pengguna = ?', [$id]);
        Database::exec(
            "UPDATE pengguna SET nama = ?, username = ?, password_hash = ?, no_hp = NULL, nik = NULL, status_akun = 'nonaktif'
              WHERE id_pengguna = ?",
            ['Mantan Penyewa #' . $id, 'terhapus_' . $id, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), $id]
        );
    }

    public function count(): int
    {
        return (int)Database::val('SELECT COUNT(*) FROM pengguna');
    }
}
