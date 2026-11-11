<?php
declare(strict_types=1);

class GoogleAuthController extends Controller
{
    /** Mulai alur: alihkan ke Google. */
    public function mulai(): void
    {
        if (!GoogleAuthService::aktif()) {
            flash('error', 'Login Google belum diaktifkan.');
            $this->redirect('/login');
        }
        $mode = $this->input('mode') === 'tautkan' ? 'tautkan' : 'masuk';
        if ($mode === 'tautkan' && (!Auth::check() || Auth::role() !== 'penyewa')) {
            flash('error', 'Masuk sebagai penyewa terlebih dahulu untuk menghubungkan Google.');
            $this->redirect('/login');
        }
        if ($mode === 'masuk' && Auth::check()) {
            $this->redirect(Auth::home());
        }
        header('Location: ' . (new GoogleAuthService())->urlOtorisasi($mode));
        exit;
    }

    public function callback(): void
    {
        $tujuanGagal = Auth::check() ? '/akun' : '/login';
        try {
            if (!GoogleAuthService::aktif()) {
                throw new AppException('Login Google belum diaktifkan.');
            }
            if (!empty($_GET['error'])) {
                unset($_SESSION['g_oauth']);
                throw new AppException('Login Google dibatalkan.');
            }
            $info = (new GoogleAuthService())->verifikasiCallback($_GET);
            $auth = new AuthService();

            if ($info['mode'] === 'tautkan') {
                if (!Auth::check() || Auth::role() !== 'penyewa') {
                    throw new AppException('Masuk sebagai penyewa terlebih dahulu.');
                }
                $auth->tautkanGoogle(Auth::id(), $info['sub'], $info['email']);
                flash('success', 'Akun Google ' . $info['email'] . ' berhasil dihubungkan.');
                $this->redirect('/akun');
            }

            $u = $auth->cariDariGoogle($info['sub']);
            if ($u !== null) {
                Auth::login($u);
                flash('success', 'Selamat datang, ' . $u['nama'] . '.');
                $this->redirect(Auth::home());
            }
            if ($auth->emailTerpakai($info['email'])) {
                throw new AppException('Email ' . $info['email'] . ' sudah terdaftar. Masuk dengan username dan password, lalu hubungkan Google di menu Akun Saya.');
            }
            $_SESSION['google_pending'] = ['sub' => $info['sub'], 'email' => $info['email'], 'nama' => $info['nama'], 't' => time()];
            $this->redirect('/daftar/google');
        } catch (AppException $e) {
            flash('error', $e->getMessage());
            $this->redirect($tujuanGagal);
        }
    }

    /** Halaman melengkapi data (NIK, HP, alamat) setelah autentikasi Google. */
    public function formLengkapi(): void
    {
        $this->view('auth/google_lengkapi', ['title' => 'Lengkapi Data', 'p' => $this->pending()]);
    }

    public function simpanLengkapi(): void
    {
        $p = $this->pending();
        $u = (new AuthService())->daftarDariGoogle($p, $_POST);
        unset($_SESSION['google_pending']);
        Auth::login($u);
        flash('success', 'Pendaftaran berhasil. Selamat datang, ' . $u['nama'] . '.');
        $this->redirect(Auth::home());
    }

    private function pending(): array
    {
        $p = $_SESSION['google_pending'] ?? null;
        if (!is_array($p) || time() - (int)$p['t'] > 900) {
            unset($_SESSION['google_pending']);
            flash('warn', 'Sesi pendaftaran Google berakhir. Silakan ulangi.');
            $this->redirect('/daftar');
        }
        return $p;
    }
}
