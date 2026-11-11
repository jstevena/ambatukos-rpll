<?php
declare(strict_types=1);

class NotifikasiController extends Controller
{
    private NotifikasiService $svc;

    public function __construct()
    {
        $this->svc = new NotifikasiService();
    }

    /** UI-16 Notifikasi. */
    public function index(): void
    {
        $page = $this->page();
        $this->view('notifikasi/index', ['title' => 'Notifikasi', 'd' => $this->svc->daftar(Auth::id(), $page), 'page' => $page]);
    }

    public function baca(string $id): void
    {
        $this->svc->tandaiDibaca(Auth::id(), (int)$id);
        $this->redirect('/notifikasi');
    }

    public function bacaSemua(): void
    {
        $this->svc->tandaiSemua(Auth::id());
        $this->redirect('/notifikasi');
    }
}
