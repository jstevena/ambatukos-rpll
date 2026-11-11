<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function formLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::home());
        }
        $this->view('auth/login', ['title' => 'Masuk', 'googleAktif' => GoogleAuthService::aktif()]);
    }

    public function login(): void
    {
        $u = $this->auth->autentikasi((string)($_POST['username'] ?? ''), (string)($_POST['password'] ?? ''));
        Auth::login($u);
        flash('success', 'Selamat datang, ' . $u['nama'] . '.');
        $this->redirect(Auth::home());
    }

    public function formRegistrasi(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::home());
        }
        $this->view('auth/register', ['title' => 'Daftar Akun', 'googleAktif' => GoogleAuthService::aktif()]);
    }

    public function registrasi(): void
    {
        $this->auth->registrasi($_POST);
        flash('success', 'Registrasi berhasil. Silakan masuk dengan akun Anda.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        flash('success', 'Anda telah keluar.');
        $this->redirect('/login');
    }
}
