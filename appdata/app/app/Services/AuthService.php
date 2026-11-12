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
        $hpMentah = trim((string)($in['no_hp'] ?? ''));
        $noHp = normalisasi_hp($hpMentah);
        $nik = trim((string)($in['nik'] ?? ''));

        if ($nama === '' || $username === '' || $password === '' || $hpMentah === '' || $nik === '') {
            throw new AppException('Semua kolom wajib diisi.');
        }
        if (mb_strlen($nama) > 100) {
            throw new AppException('Nama maksimal 100 karakter.');
        }
        if (!preg_match('/^[A-Za-z0-9_.]{4,50}$/', $username)) {
            throw new AppException('Username 4-50 karakter: huruf, angka, titik, atau garis bawah.');
        }
        $this->pastikanPasswordKuat($password);
        if ($password !== (string)($in['password2'] ?? '')) {
            throw new AppException('Konfirmasi password tidak sama.');
        }
        if ($noHp === null) {
            throw new AppException('Nomor HP tidak valid. Gunakan format 08xx atau +62xx.');
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
                'no_hp' => $noHp, 'nik' => $nik,
                'peran' => 'penyewa', 'status_akun' => 'aktif', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Username sudah dipakai.');
            }
            throw $e;
        }
    }

    /** Aturan password: 8-72 karakter, ada huruf besar, huruf kecil, dan angka. */
    public function pastikanPasswordKuat(string $password): void
    {
        if (strlen($password) < 8 || strlen($password) > 72
            || !preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new AppException('Password 8-72 karakter dan harus mengandung huruf besar, huruf kecil, dan angka.');
        }
    }

    /** Ubah password sendiri (semua peran): wajib memasukkan password saat ini. */
    public function ubahPassword(int $idPengguna, string $lama, string $baru, string $konfirmasi): void
    {
        $u = $this->repo->byId($idPengguna);
        if ($u === null || !password_verify($lama, $u['password_hash'])) {
            throw new AppException('Password saat ini salah.');
        }
        if ($baru !== $konfirmasi) {
            throw new AppException('Konfirmasi password baru tidak sama.');
        }
        $this->pastikanPasswordKuat($baru);
        if (hash_equals($lama, $baru)) {
            throw new AppException('Password baru harus berbeda dari password saat ini.');
        }
        $this->repo->updatePassword($idPengguna, password_hash($baru, PASSWORD_DEFAULT));
    }

    // ---------- Login Google ----------

    public function cariDariGoogle(string $sub): ?array
    {
        $u = $this->repo->byGoogleId($sub);
        if ($u !== null && $u['status_akun'] !== 'aktif') {
            throw new AppException('Akun belum aktif. Hubungi pengelola kos.');
        }
        return $u;
    }

    public function emailTerpakai(string $email): bool
    {
        return $this->repo->byEmail($email) !== null;
    }

    public function infoGoogle(int $idPengguna): array
    {
        $u = $this->repo->byId($idPengguna);
        return ['terhubung' => !empty($u['google_id']), 'email' => $u['email'] ?? null];
    }

    /** Hubungkan Google ke akun penyewa yang sedang login (pencocokan lewat google_id, bukan email). */
    public function tautkanGoogle(int $idPengguna, string $sub, string $email): void
    {
        $u = $this->repo->byId($idPengguna);
        if ($u === null || $u['peran'] !== 'penyewa') {
            throw new AppException('Hanya akun penyewa yang dapat dihubungkan dengan Google.');
        }
        if (!empty($u['google_id'])) {
            throw new AppException('Akun ini sudah terhubung dengan Google.');
        }
        $lain = $this->repo->byGoogleId($sub);
        if ($lain !== null && (int)$lain['id_pengguna'] !== $idPengguna) {
            throw new AppException('Akun Google ini sudah terhubung dengan akun lain.');
        }
        $emailLain = $this->repo->byEmail($email);
        if ($emailLain !== null && (int)$emailLain['id_pengguna'] !== $idPengguna) {
            throw new AppException('Email Google ini sudah dipakai akun lain.');
        }
        $this->repo->update($idPengguna, ['google_id' => $sub, 'email' => $email]);
    }

    /** Pendaftaran penyewa baru lewat Google; NIK dan nomor HP tetap wajib diisi. */
    public function daftarDariGoogle(array $pending, array $in): array
    {
        $nama = trim((string)($in['nama'] ?? ''));
        $hpMentah = trim((string)($in['no_hp'] ?? ''));
        $noHp = normalisasi_hp($hpMentah);
        $nik = trim((string)($in['nik'] ?? ''));
        if ($nama === '' || $hpMentah === '' || $nik === '') {
            throw new AppException('Semua kolom wajib diisi.');
        }
        if (mb_strlen($nama) > 100) {
            throw new AppException('Nama maksimal 100 karakter.');
        }
        if ($noHp === null) {
            throw new AppException('Nomor HP tidak valid. Gunakan format 08xx atau +62xx.');
        }
        if (!preg_match('/^[0-9]{16}$/', $nik)) {
            throw new AppException('NIK harus 16 digit angka.');
        }
        if ($this->repo->byGoogleId($pending['sub']) !== null || $this->repo->byEmail($pending['email']) !== null) {
            throw new AppException('Akun Google ini sudah terdaftar. Silakan masuk dengan Google.');
        }
        try {
            $id = $this->repo->create([
                'nama' => $nama, 'username' => $this->usernameDariEmail($pending['email']),
                'email' => $pending['email'], 'google_id' => $pending['sub'],
                'password_hash' => password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), // tidak dapat dipakai login
                'no_hp' => $noHp, 'nik' => $nik,
                'peran' => 'penyewa', 'status_akun' => 'aktif', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Akun sudah terdaftar atau username bentrok. Silakan coba lagi.');
            }
            throw $e;
        }
        return $this->repo->byId($id);
    }

    private function usernameDariEmail(string $email): string
    {
        $base = preg_replace('/[^A-Za-z0-9_.]/', '', (string)strstr($email, '@', true));
        if (strlen($base) < 4) {
            $base .= 'user';
        }
        $base = substr($base, 0, 40);
        $u = $base;
        for ($i = 0; $i < 10 && $this->repo->byUsername($u) !== null; $i++) {
            $u = $base . random_int(100, 9999);
        }
        return $u;
    }

    public function noHp(int $idPengguna): ?string
    {
        return $this->repo->byId($idPengguna)['no_hp'] ?? null;
    }

    /** Setiap pengguna dapat mengubah nomor HP-nya sendiri (disimpan dalam format +62). */
    public function ubahNoHp(int $idPengguna, string $hp): string
    {
        $baru = normalisasi_hp(trim($hp));
        if ($baru === null) {
            throw new AppException('Nomor HP tidak valid. Gunakan format 08xx atau +62xx.');
        }
        $this->repo->update($idPengguna, ['no_hp' => $baru]);
        return $baru;
    }

    /** Setiap pengguna dapat mengubah namanya sendiri. */
    public function ubahNama(int $idPengguna, string $nama): string
    {
        $nama = trim($nama);
        if ($nama === '') {
            throw new AppException('Nama tidak boleh kosong.');
        }
        if (mb_strlen($nama) > 100) {
            throw new AppException('Nama maksimal 100 karakter.');
        }
        $this->repo->update($idPengguna, ['nama' => $nama]);
        return $nama;
    }

    public function validasiUsername(string $username): void
    {
        if (!preg_match('/^[A-Za-z0-9_.]{4,50}$/', $username)) {
            throw new AppException('Username 4-50 karakter: huruf, angka, titik, atau garis bawah.');
        }
    }

    /** Admin/pemilik mengubah username sendiri. Penyewa tidak diizinkan (diubah oleh admin/pemilik). */
    public function ubahUsername(int $idPengguna, string $usernameBaru, string $password): string
    {
        $u = $this->repo->byId($idPengguna);
        if ($u === null) {
            throw new AppException('Akun tidak ditemukan.');
        }
        if ($u['peran'] === 'penyewa') {
            throw new AppException('Penyewa tidak dapat mengubah username. Hubungi admin.');
        }
        if (!password_verify($password, $u['password_hash'])) {
            throw new AppException('Password saat ini salah.');
        }
        $usernameBaru = trim($usernameBaru);
        $this->validasiUsername($usernameBaru);
        $ada = $this->repo->byUsername($usernameBaru);
        if ($ada !== null && (int)$ada['id_pengguna'] !== $idPengguna) {
            throw new AppException('Username sudah dipakai.');
        }
        try {
            $this->repo->update($idPengguna, ['username' => $usernameBaru]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Username sudah dipakai.');
            }
            throw $e;
        }
        return $usernameBaru;
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
