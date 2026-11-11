<?php
declare(strict_types=1);

class PenggunaRepository extends Repository
{
    public function byUsername(string $username): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE username = ?', [$username]);
    }

    public function byId(int $id): ?array
    {
        return Database::one('SELECT * FROM pengguna WHERE id_pengguna = ?', [$id]);
    }

    public function create(array $data): int
    {
        return $this->insert('pengguna', $data);
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

    public function count(): int
    {
        return (int)Database::val('SELECT COUNT(*) FROM pengguna');
    }
}
