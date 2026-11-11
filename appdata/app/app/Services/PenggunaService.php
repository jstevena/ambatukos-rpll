<?php
declare(strict_types=1);

/** Manajemen pengguna oleh admin dan pemilik kos: lihat, nonaktifkan/aktifkan, hapus. */
class PenggunaService
{
    public const PER_HALAMAN = 12;

    private PenggunaRepository $repo;
    private AuthService $auth;

    public function __construct()
    {
        $this->repo = new PenggunaRepository();
        $this->auth = new AuthService();
    }

    /** Akun pemilik kos hanya dapat dikelola oleh pemilik kos sendiri. */
    private function pastikanBolehMengelola(string $peranAktor, array $target): void
    {
        if ($peranAktor === 'admin' && $target['peran'] === 'pemilik') {
            throw new AppException('Admin tidak dapat menonaktifkan atau menghapus akun pemilik kos.');
        }
    }

    /** Peran yang boleh dibuat: admin -> penyewa; pemilik -> penyewa & admin. */
    public function peranBolehDibuat(string $peranAktor): array
    {
        return $peranAktor === 'pemilik' ? ['penyewa' => 'Penyewa', 'admin' => 'Admin'] : ['penyewa' => 'Penyewa'];
    }

    /** @return string nama akun yang dibuat */
    public function buat(string $peranAktor, array $in): string
    {
        $peran = (string)($in['peran'] ?? 'penyewa');
        if (!array_key_exists($peran, $this->peranBolehDibuat($peranAktor))) {
            throw new AppException('Anda tidak berwenang membuat akun dengan peran tersebut.');
        }
        $nama = trim((string)($in['nama'] ?? ''));
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');
        $noHp = preg_replace('/[\s-]/', '', (string)($in['no_hp'] ?? ''));
        $nik = trim((string)($in['nik'] ?? ''));

        if ($nama === '' || $username === '' || $password === '') {
            throw new AppException('Nama, username, dan password wajib diisi.');
        }
        if ($peran === 'penyewa' && ($noHp === '' || $nik === '')) {
            throw new AppException('Untuk akun penyewa, nomor HP dan NIK wajib diisi.');
        }
        if (mb_strlen($nama) > 100) {
            throw new AppException('Nama maksimal 100 karakter.');
        }
        $this->auth->validasiUsername($username);
        $this->auth->pastikanPasswordKuat($password);
        if ($password !== (string)($in['password2'] ?? '')) {
            throw new AppException('Konfirmasi password tidak sama.');
        }
        if ($noHp !== '' && !preg_match('/^[0-9+]{8,20}$/', $noHp)) {
            throw new AppException('Nomor HP tidak valid.');
        }
        if ($nik !== '' && !preg_match('/^[0-9]{16}$/', $nik)) {
            throw new AppException('NIK harus 16 digit angka.');
        }
        if ($this->repo->byUsername($username) !== null) {
            throw new AppException('Username sudah dipakai.');
        }
        try {
            $this->repo->create([
                'nama' => $nama, 'username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'no_hp' => $noHp !== '' ? $noHp : null, 'nik' => $nik !== '' ? $nik : null,
                'peran' => $peran, 'status_akun' => 'aktif', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Username sudah dipakai.');
            }
            throw $e;
        }
        return $nama;
    }

    /** Akun penyewa yang boleh diubah username/password-nya oleh admin atau pemilik. */
    public function penyewaUntukDiubah(int $id): array
    {
        $u = $this->repo->byId($id);
        if ($u === null || $u['peran'] !== 'penyewa') {
            throw new AppException('Hanya akun penyewa yang dapat diubah dari sini.');
        }
        if (str_starts_with((string)$u['username'], 'terhapus_')) {
            throw new AppException('Data pribadi akun ini sudah dihapus.');
        }
        return $u;
    }

    /** @return string nama penyewa */
    public function ubahKredensialPenyewa(int $id, string $username, string $password): string
    {
        $u = $this->penyewaUntukDiubah($id);
        $username = trim($username);
        $this->auth->validasiUsername($username);
        $ada = $this->repo->byUsername($username);
        if ($ada !== null && (int)$ada['id_pengguna'] !== $id) {
            throw new AppException('Username sudah dipakai.');
        }
        $data = ['username' => $username];
        if ($password !== '') {
            $this->auth->pastikanPasswordKuat($password);
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }
        try {
            $this->repo->update($id, $data);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Username sudah dipakai.');
            }
            throw $e;
        }
        return $u['nama'];
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
