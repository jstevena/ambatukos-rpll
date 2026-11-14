<?php
declare(strict_types=1);

class PenggunaController extends Controller
{
    private PenggunaService $svc;

    public function __construct()
    {
        $this->svc = new PenggunaService();
    }

    public function index(): void
    {
        $peran = $this->input('peran') ?: null;
        $q = $this->input('q');
        $page = $this->page();
        $this->view('pengguna/index', [
            'title' => 'Pengguna', 'd' => $this->svc->daftar($peran, $q, $page),
            'peran' => $peran, 'q' => $q, 'page' => $page,
        ]);
    }

    public function formBaru(): void
    {
        $this->view('pengguna/baru', ['title' => 'Tambah Pengguna', 'peranBoleh' => $this->svc->peranBolehDibuat((string)Auth::role())]);
    }

    public function simpanBaru(): void
    {
        $nama = $this->svc->buat((string)Auth::role(), $_POST);
        flash('success', 'Akun "' . $nama . '" berhasil dibuat.');
        $this->redirect('/pengguna');
    }

    public function formUbah(string $id): void
    {
        $this->view('pengguna/ubah', ['title' => 'Ubah Akun', 'u' => $this->svc->targetUntukDiubah((string)Auth::role(), (int)$id)]);
    }

    public function ubah(string $id): void
    {
        $nama = $this->svc->ubahAkun((string)Auth::role(), (int)$id, $_POST);
        flash('success', 'Akun "' . $nama . '" diperbarui.');
        $this->redirect('/pengguna');
    }

    public function blacklist(string $id): void
    {
        $flag = $this->input('nilai') === '1';
        $nama = $this->svc->ubahBlacklist((int)$id, $flag);
        flash('success', $flag ? 'Penyewa "' . $nama . '" masuk daftar hitam.' : 'Blacklist "' . $nama . '" dicabut.');
        $this->redirect('/pengguna');
    }

    public function hapus(string $id): void
    {
        $r = $this->svc->hapus(Auth::id(), (string)Auth::role(), (int)$id);
        flash('success', $r['anonim']
            ? 'Data pribadi "' . $r['nama'] . '" dihapus. Riwayat transaksinya disimpan tanpa identitas.'
            : 'Pengguna "' . $r['nama'] . '" dihapus.');
        $this->redirect('/pengguna');
    }

    public function status(string $id): void
    {
        $status = $this->input('status');
        $nama = $this->svc->ubahStatus(Auth::id(), (string)Auth::role(), (int)$id, $status);
        flash('success', 'Akun "' . $nama . '" ' . ($status === 'aktif' ? 'diaktifkan.' : 'dinonaktifkan. Pengguna tidak dapat masuk lagi.'));
        $this->redirect('/pengguna');
    }
}
