<?php
declare(strict_types=1);

class PengumumanSewaController extends Controller
{
    private PengumumanSewaService $svc;

    public function __construct()
    {
        $this->svc = new PengumumanSewaService();
    }

    private function pesanDiumumkan(int $n): string
    {
        return $n > 0
            ? "Kenaikan sewa diumumkan. Notifikasi dikirim ke $n penyewa."
            : 'Kenaikan sewa diumumkan. Kamar ini belum memiliki penyewa aktif sehingga tidak ada notifikasi terkirim.';
    }

    /** UI-11 Pengumuman Kenaikan Sewa (admin). */
    public function admin(): void
    {
        $this->view('pengumuman/admin', ['title' => 'Pengumuman Sewa', 'daftar' => $this->svc->daftar()]);
    }

    public function umumkan(string $id): void
    {
        $n = $this->svc->umumkan(Auth::id(), 'admin', (int)$id);
        flash('success', $this->pesanDiumumkan($n));
        $this->redirect('/admin/pengumuman');
    }

    /** UI-12 Penetapan Kenaikan Sewa (pemilik). */
    public function pemilik(): void
    {
        $this->view('pengumuman/pemilik', [
            'title' => 'Kenaikan Sewa', 'daftar' => $this->svc->daftar(), 'kamar' => (new KamarService())->semua(),
            'minTanggal' => date('Y-m-d', strtotime('+' . PengumumanSewaService::MIN_HARI . ' days')),
        ]);
    }

    public function tetapkan(): void
    {
        $langsung = $this->input('aksi') === 'umumkan';
        $n = $this->svc->tetapkan(
            Auth::id(), (int)($_POST['id_kamar'] ?? 0), $this->input('biaya_baru'), $this->input('tanggal_berlaku'), $langsung
        );
        flash('success', $n === null
            ? 'Kenaikan sewa ditetapkan. Admin telah diberi tahu untuk mengumumkannya.'
            : 'Kenaikan sewa ditetapkan dan langsung diumumkan. ' . ($n > 0 ? "Notifikasi dikirim ke $n penyewa." : 'Kamar ini belum memiliki penyewa aktif sehingga tidak ada notifikasi terkirim.'));
        $this->redirect('/pemilik/kenaikan-sewa');
    }

    /** Pemilik mengumumkan sendiri penetapan yang masih berstatus Ditetapkan. */
    public function umumkanPemilik(string $id): void
    {
        $n = $this->svc->umumkan(Auth::id(), 'pemilik', (int)$id);
        flash('success', $this->pesanDiumumkan($n));
        $this->redirect('/pemilik/kenaikan-sewa');
    }
}
