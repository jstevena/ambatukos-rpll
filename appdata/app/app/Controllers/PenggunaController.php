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
