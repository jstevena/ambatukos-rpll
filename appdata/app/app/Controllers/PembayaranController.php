<?php
declare(strict_types=1);

class PembayaranController extends Controller
{
    private PembayaranService $svc;

    public function __construct()
    {
        $this->svc = new PembayaranService();
    }

    /** UI-06 Status dan Riwayat Pembayaran (penyewa). */
    public function index(): void
    {
        $tagihan = new TagihanService();
        $page = $this->page();
        $riwayat = $tagihan->riwayat(Auth::id(), $page);
        foreach ($riwayat['rows'] as &$t) {
            $t['bisa_unggah'] = $this->svc->bolehUnggah($t);
        }
        unset($t);
        $berikutnya = $tagihan->berikutnya(Auth::id());
        if ($berikutnya !== null) {
            $berikutnya['bisa_unggah'] = $this->svc->bolehUnggah($berikutnya);
        }
        $this->view('pembayaran/index', ['title' => 'Pembayaran', 'berikutnya' => $berikutnya, 'riwayat' => $riwayat, 'page' => $page]);
    }

    /** UI-07 Upload Bukti Pembayaran. */
    public function formUnggah(string $id): void
    {
        $t = (new TagihanService())->detail((int)$id);
        if ($t === null || (int)$t['id_penyewa'] !== Auth::id()) {
            $this->notFound();
        }
        $this->view('pembayaran/upload', ['title' => 'Upload Bukti Pembayaran', 't' => $t, 'bisa' => $this->svc->bolehUnggah($t), 'bank' => env('BANK_INFO', '')]);
    }

    public function unggah(string $id): void
    {
        $this->svc->unggah(Auth::id(), (int)$id, $_FILES['bukti'] ?? []);
        flash('success', 'Bukti pembayaran terkirim. Status: Menunggu Verifikasi.');
        $this->redirect('/pembayaran');
    }

    /** Bukti transfer dilayani lewat controller agar akses dapat dibatasi (NFR-02). */
    public function bukti(string $id): void
    {
        $pb = $this->svc->detail((int)$id);
        if ($pb === null) {
            $this->notFound();
        }
        if (!$this->svc->bolehLihatBukti(Auth::user(), $pb)) {
            http_response_code(403);
            View::render('errors/403', ['title' => 'Akses ditolak']);
            exit;
        }
        $abs = (new FileStorageHandler())->pathAbsolut($pb['bukti_transfer']);
        if ($abs === null || !is_file($abs)) {
            $this->notFound();
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($abs) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($abs));
        header('Content-Disposition: inline; filename="bukti-' . (int)$id . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0');
        readfile($abs);
        exit;
    }

    // ---------- Admin ----------

    /** UI-08 Verifikasi Pembayaran + pengembalian DP (UC-15). */
    public function verifikasi(): void
    {
        $this->view('pembayaran/verifikasi', [
            'title' => 'Verifikasi Pembayaran',
            'menunggu' => $this->svc->menungguVerifikasi(),
            'pengembalian' => $this->svc->menungguPengembalian(),
        ]);
    }

    public function tinjau(string $id): void
    {
        $pb = $this->svc->detail((int)$id);
        if ($pb === null) {
            $this->notFound();
        }
        $this->view('pembayaran/tinjau', ['title' => 'Tinjau Pembayaran', 'pb' => $pb]);
    }

    public function setujui(string $id): void
    {
        $this->svc->setujui((int)$id, Auth::id());
        flash('success', 'Pembayaran disetujui. Status tagihan, pemesanan, dan kamar diperbarui.');
        $this->redirect('/admin/verifikasi');
    }

    public function tolak(string $id): void
    {
        $this->svc->tolak((int)$id, Auth::id(), $this->input('catatan'));
        flash('success', 'Pembayaran ditolak. Penyewa diminta mengunggah ulang.');
        $this->redirect('/admin/verifikasi');
    }

    public function kembalikanDp(string $id): void
    {
        $this->svc->kembalikanDp((int)$id, Auth::id(), $this->input('catatan'));
        flash('success', 'DP ditandai Dikembalikan dan penyewa diberi tahu.');
        $this->redirect('/admin/verifikasi');
    }

    /** UI-10 Penyewa Belum Bayar. */
    public function belumBayar(): void
    {
        $this->view('pembayaran/belum_bayar', ['title' => 'Penyewa Belum Bayar', 'daftar' => $this->svc->belumBayar()]);
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Halaman tidak ditemukan']);
        exit;
    }
}
