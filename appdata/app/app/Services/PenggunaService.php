<?php
declare(strict_types=1);

/** Manajemen pengguna oleh admin dan pemilik kos: lihat, nonaktifkan/aktifkan, hapus. */
class PenggunaService
{
    public const PER_HALAMAN = 12;

    private PenggunaRepository $repo;

    public function __construct()
    {
        $this->repo = new PenggunaRepository();
    }

    /** Akun pemilik kos hanya dapat dikelola oleh pemilik kos sendiri. */
    private function pastikanBolehMengelola(string $peranAktor, array $target): void
    {
        if ($peranAktor === 'admin' && $target['peran'] === 'pemilik') {
            throw new AppException('Admin tidak dapat menonaktifkan atau menghapus akun pemilik kos.');
        }
    }

    public function daftar(?string $peran, string $q, int $page): array
    {
        if (!in_array($peran, ['penyewa', 'mantan', 'admin', 'pemilik'], true)) {
            $peran = null;
        }
        [$rows, $total] = $this->repo->paginate($peran, trim($q), $page, self::PER_HALAMAN);
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)max(1, ceil($total / self::PER_HALAMAN))];
    }

    /** @return array{nama: string, anonim: bool} anonim = true bila hanya data pribadi yang dihapus */
    public function hapus(int $idAktor, string $peranAktor, int $id): array
    {
        if ($id === $idAktor) {
            throw new AppException('Anda tidak dapat menghapus akun Anda sendiri.');
        }
        $u = $this->repo->byId($id);
        if ($u === null) {
            throw new AppException('Pengguna tidak ditemukan.');
        }
        $this->pastikanBolehMengelola($peranAktor, $u);
        if (in_array($u['peran'], ['admin', 'pemilik'], true) && $this->repo->countByPeran($u['peran']) <= 1) {
            throw new AppException('Tidak dapat menghapus satu-satunya akun ' . $u['peran'] . '.');
        }
        try {
            Database::transaction(fn() => $this->repo->hapus($id));
            return ['nama' => $u['nama'], 'anonim' => false];
        } catch (PDOException $e) {
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
        // Punya riwayat transaksi: baris tidak boleh hilang (FK RESTRICT). Mantan penyewa -> hapus data pribadi saja.
        if ($u['peran'] !== 'penyewa') {
            throw new AppException('Akun "' . $u['nama'] . '" terkait data kamar/pembayaran sehingga tidak dapat dihapus. Nonaktifkan saja akunnya.');
        }
        if ($this->repo->punyaPemesananBerjalan($id)) {
            throw new AppException('Pengguna masih memiliki pemesanan atau sewa yang berjalan. Batalkan pemesanan / akhiri sewa terlebih dahulu.');
        }
        if ($this->repo->punyaTagihanBelumLunas($id)) {
            throw new AppException('Pengguna masih memiliki tagihan yang belum lunas.');
        }
        if ($this->repo->punyaPengembalianTertunda($id)) {
            throw new AppException('Masih ada pengembalian DP yang belum diproses. Selesaikan dulu di menu Verifikasi Pembayaran.');
        }
        Database::transaction(fn() => $this->repo->anonimkan($id));
        return ['nama' => $u['nama'], 'anonim' => true];
    }

    public function ubahStatus(int $idAktor, string $peranAktor, int $id, string $status): string
    {
        if (!in_array($status, ['aktif', 'nonaktif'], true)) {
            throw new AppException('Status tidak valid.');
        }
        if ($id === $idAktor) {
            throw new AppException('Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }
        $u = $this->repo->byId($id);
        if ($u === null) {
            throw new AppException('Pengguna tidak ditemukan.');
        }
        $this->pastikanBolehMengelola($peranAktor, $u);
        if ($status === 'nonaktif' && in_array($u['peran'], ['admin', 'pemilik'], true)
            && $u['status_akun'] === 'aktif' && $this->repo->countByPeran($u['peran'], true) <= 1) {
            throw new AppException('Tidak dapat menonaktifkan satu-satunya akun ' . $u['peran'] . ' yang aktif.');
        }
        $this->repo->setStatus($id, $status);
        return $u['nama'];
    }
}
