<?php
declare(strict_types=1);

class AuthService
{
    private PenggunaRepository $repo;

    public function __construct()
    {
        $this->repo = new PenggunaRepository();
    }

    /** UC-01 Registrasi Akun: selalu berperan penyewa (calon penyewa = penyewa tanpa pemesanan aktif). */
    public function registrasi(array $in): int
    {
        $nama = trim((string)($in['nama'] ?? ''));
        $username = trim((string)($in['username'] ?? ''));
        $password = (string)($in['password'] ?? '');
        $noHp = preg_replace('/[\s-]/', '', (string)($in['no_hp'] ?? ''));
        $nik = trim((string)($in['nik'] ?? ''));
        $alamat = trim((string)($in['alamat'] ?? ''));

        if ($nama === '' || $username === '' || $password === '' || $noHp === '' || $nik === '' || $alamat === '') {
            throw new AppException('Semua kolom wajib diisi.');
        }
        if (mb_strlen($nama) > 100 || mb_strlen($alamat) > 255) {
            throw new AppException('Nama maksimal 100 karakter dan alamat maksimal 255 karakter.');
        }
        if (!preg_match('/^[A-Za-z0-9_.]{4,50}$/', $username)) {
            throw new AppException('Username 4-50 karakter: huruf, angka, titik, atau garis bawah.');
        }
        if (strlen($password) < 8 || strlen($password) > 72
            || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new AppException('Password 8-72 karakter dan harus mengandung huruf besar, huruf kecil, dan angka.');
        }
        if ($password !== (string)($in['password2'] ?? '')) {
            throw new AppException('Konfirmasi password tidak sama.');
        }
        if (!preg_match('/^[0-9+]{8,20}$/', $noHp)) {
            throw new AppException('Nomor HP tidak valid.');
        }
        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            throw new AppException('NIK harus 16 digit angka.');
        }
        if ($this->repo->byUsername($username) !== null) {
            throw new AppException('Username sudah dipakai.');
        }
        try {
            return $this->repo->create([
                'nama' => $nama, 'username' => $username,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'no_hp' => $noHp, 'nik' => $nik, 'alamat' => $alamat,
                'peran' => 'penyewa', 'status_akun' => 'aktif', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Username sudah dipakai.');
            }
            throw $e;
        }
    }

    /** UC-02 Login: verifikasi hash, cek status akun, kembalikan data pengguna. */
    public function autentikasi(string $username, string $password): array
    {
        $u = $this->repo->byUsername(trim($username));
        if ($u === null || !password_verify($password, $u['password_hash'])) {
            throw new AppException('Username atau password salah.');
        }
        if ($u['status_akun'] !== 'aktif') {
            throw new AppException('Akun belum aktif. Hubungi pengelola kos.');
        }
        return $u;
    }
}
