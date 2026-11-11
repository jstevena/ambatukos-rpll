<?php
declare(strict_types=1);

class AkunController extends Controller
{
    public function form(): void
    {
        $this->view('akun/password', ['title' => 'Akun Saya', 'user' => Auth::user()]);
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
