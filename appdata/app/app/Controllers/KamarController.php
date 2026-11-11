<?php
declare(strict_types=1);

class KamarController extends Controller
{
    private KamarService $svc;

    public function __construct()
    {
        $this->svc = new KamarService();
    }

    /** UI-03 Daftar Kamar. */
    public function index(): void
    {
        $status = $this->input('status') ?: null;
        $page = $this->page();
        $d = $this->svc->daftar($status, $page);
        $this->view('kamar/index', ['title' => 'Daftar Kamar', 'd' => $d, 'status' => $status, 'page' => $page]);
    }

    /** UI-04 Detail Kamar. */
    public function show(string $id): void
    {
        $k = $this->svc->detail((int)$id);
        if ($k === null) {
            $this->notFound();
        }
        $this->view('kamar/show', ['title' => 'Kamar ' . $k['nomor_kamar'], 'k' => $k, 'dpPersen' => PemesananService::persenDp()]);
    }

    // ---------- Admin: UI-09 Kelola Data Kamar ----------

    public function kelola(): void
    {
        $page = $this->page();
        $this->view('kamar/kelola', [
            'title' => 'Kelola Kamar', 'd' => $this->svc->daftar(null, $page), 'page' => $page,
            'sewaAktif' => $this->svc->penyewaAktifPerKamar(),
        ]);
    }

    public function formBaru(): void
    {
        $this->view('kamar/form', ['title' => 'Tambah Kamar', 'k' => null]);
    }

    public function simpanBaru(): void
    {
        $this->svc->simpan(null, $this->svc->pemilikId(), $_POST, FileStorageHandler::normalisasi($_FILES['foto'] ?? null));
        flash('success', 'Kamar berhasil ditambahkan.');
        $this->redirect('/admin/kamar');
    }

    public function formUbah(string $id): void
    {
        $k = $this->svc->detail((int)$id);
        if ($k === null) {
            $this->notFound();
        }
        $this->view('kamar/form', ['title' => 'Ubah Kamar ' . $k['nomor_kamar'], 'k' => $k]);
    }

    public function simpanUbah(string $id): void
    {
        $this->svc->simpan((int)$id, $this->svc->pemilikId(), $_POST, FileStorageHandler::normalisasi($_FILES['foto'] ?? null));
        flash('success', 'Data kamar berhasil diperbarui.');
        $this->redirect('/admin/kamar');
    }

    public function hapus(string $id): void
    {
        $this->svc->hapus((int)$id);
        flash('success', 'Kamar berhasil dihapus.');
        $this->redirect('/admin/kamar');
    }

    public function hapusFoto(string $id): void
    {
        $idKamar = $this->svc->hapusFoto((int)$id);
        flash('success', 'Foto dihapus.');
        $this->redirect('/admin/kamar/' . $idKamar . '/ubah');
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Halaman tidak ditemukan']);
        exit;
    }
}
