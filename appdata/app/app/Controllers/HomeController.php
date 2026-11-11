<?php
declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $this->redirect(Auth::check() ? Auth::home() : '/kamar');
    }

    /** UI-17 Dashboard Admin (Tabel 5.18) dan Pemilik Kos. */
    public function dashboard(): void
    {
        $kamar = new KamarService();
        $laporan = new LaporanService();
        $bayar = new PembayaranService();
        $status = $kamar->ringkasStatus();
        $data = [
            'title' => 'Dashboard',
            'status' => $status,
            'totalKamar' => array_sum($status),
            'seri' => $laporan->enamBulan(),
        ];
        if (Auth::role() === 'admin') {
            $this->view('dashboard/admin', $data + [
                'menunggu' => array_slice($bayar->menungguVerifikasi(), 0, 8),
                'jumlahMenunggu' => $bayar->jumlahMenungguVerifikasi(),
                'belumBayar' => $bayar->jumlahPenyewaBelumBayar(),
            ]);
            return;
        }
        $this->view('dashboard/pemilik', $data + [
            'bulanIni' => $laporan->totalBulanIni(),
            'ditetapkan' => (new PengumumanSewaService())->jumlahDitetapkan(),
            'belumBayar' => $bayar->jumlahPenyewaBelumBayar(),
        ]);
    }

    public function beranda(): void
    {
        $uid = Auth::id();
        $pemesanan = new PemesananService();
        $semua = $pemesanan->daftarPenyewa($uid);
        $this->view('dashboard/penyewa', [
            'title' => 'Beranda',
            'aktif' => array_values(array_filter($semua, static fn($p) => $p['status'] === 'Aktif')),
            'berjalan' => array_values(array_filter($semua, static fn($p) => in_array($p['status'], ['Menunggu DP', 'Menunggu Pelunasan'], true))),
            'berikutnya' => (new TagihanService())->berikutnya($uid),
            'notifikasi' => (new NotifikasiService())->terbaru($uid, 4),
        ]);
    }
}
