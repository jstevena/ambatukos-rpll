<?php
declare(strict_types=1);

class AkunController extends Controller
{
    public function form(): void
    {
        $this->view('akun/password', [
            'title' => 'Akun Saya', 'user' => Auth::user(),
            'googleAktif' => GoogleAuthService::aktif(), 'google' => (new AuthService())->infoGoogle(Auth::id()),
        ]);
    }

    public function ubahNama(): void
    {
        $nama = (new AuthService())->ubahNama(Auth::id(), (string)($_POST['nama'] ?? ''));
        $_SESSION['user']['nama'] = $nama;
        flash('success', 'Nama berhasil diubah.');
        $this->redirect('/akun');
    }

    public function ubahUsername(): void
    {
        $baru = (new AuthService())->ubahUsername(Auth::id(), (string)($_POST['username'] ?? ''), (string)($_POST['password_lama'] ?? ''));
        $_SESSION['user']['username'] = $baru;
        flash('success', 'Username berhasil diubah. Gunakan username baru saat masuk berikutnya.');
        $this->redirect('/akun');
    }

    public function ubahPassword(): void
    {
        (new AuthService())->ubahPassword(
            Auth::id(),
            (string)($_POST['password_lama'] ?? ''),
            (string)($_POST['password'] ?? ''),
            (string)($_POST['password2'] ?? '')
        );
        flash('success', 'Password berhasil diubah.');
        $this->redirect('/akun');
    }
}
