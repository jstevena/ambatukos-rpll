<?php
declare(strict_types=1);

class KeluhanController extends Controller
{
    private KeluhanService $svc;

    public function __construct()
    {
        $this->svc = new KeluhanService();
    }

    /** UI-14 Keluhan / Perbaikan. */
    public function index(): void
    {
        if (in_array(Auth::role(), ['admin', 'pemilik'], true)) {
            $this->view('keluhan/index', ['title' => 'Keluhan', 'daftar' => $this->svc->semua(), 'kamar' => []]);
            return;
        }
        $this->view('keluhan/index', [
            'title' => 'Keluhan', 'daftar' => $this->svc->byPenyewa(Auth::id()), 'kamar' => $this->svc->kamarSewaan(Auth::id()),
            'menunggak' => (new TagihanService())->menunggak(Auth::id()),
        ]);
    }

    public function ajukan(): void
    {
        $this->svc->ajukan(Auth::id(), (int)($_POST['id_kamar'] ?? 0), $this->input('judul'), $this->input('deskripsi'));
        flash('success', 'Keluhan terkirim. Admin akan menindaklanjuti.');
        $this->redirect('/keluhan');
    }

    public function ubahStatus(string $id): void
    {
        $this->svc->ubahStatus(Auth::id(), (int)$id, $this->input('status'));
        flash('success', 'Status keluhan diperbarui.');
        $this->redirect('/keluhan');
    }
}
