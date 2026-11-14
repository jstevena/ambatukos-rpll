<?php
declare(strict_types=1);

class JanjiBayarController extends Controller
{
    private JanjiBayarService $svc;

    public function __construct()
    {
        $this->svc = new JanjiBayarService();
    }

    /** Penyewa: form pengajuan janji bayar. */
    public function form(string $id): void
    {
        $t = (new TagihanService())->detail((int)$id);
        if ($t === null || (int)$t['id_penyewa'] !== Auth::id()) {
            http_response_code(404);
            View::render('errors/404', ['title' => 'Halaman tidak ditemukan']);
            exit;
        }
        $this->view('pembayaran/janji', [
            'title' => 'Ajukan Janji Bayar', 't' => $t, 'bisa' => bisa_janji($t) && $t['status_pemesanan'] === 'Aktif',
            'min' => date('Y-m-d', strtotime('+1 day')), 'maks' => date('Y-m-d', strtotime('+' . JanjiBayarService::MAKS_HARI . ' days')),
        ]);
    }

    public function ajukan(string $id): void
    {
        $this->svc->ajukan(Auth::id(), (int)$id, $this->input('tanggal_janji'), $this->input('alasan'));
        flash('success', 'Janji bayar diajukan. Admin akan meninjau; Anda akan mendapat notifikasi.');
        $this->redirect('/pembayaran');
    }

    public function setujui(string $id): void
    {
        $this->svc->setujui(Auth::id(), (int)$id);
        flash('success', 'Janji bayar disetujui. Denda dijeda sampai tanggal janji.');
        $this->redirect('/admin/belum-bayar');
    }

    public function tolak(string $id): void
    {
        $this->svc->tolak(Auth::id(), (int)$id, $this->input('catatan'));
        flash('success', 'Janji bayar ditolak.');
        $this->redirect('/admin/belum-bayar');
    }
}
