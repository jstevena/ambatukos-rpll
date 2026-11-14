<?php
declare(strict_types=1);

class PemesananController extends Controller
{
    private PemesananService $svc;

    public function __construct()
    {
        $this->svc = new PemesananService();
    }

    /** UI-05 Form Pesan Kamar. */
    public function form(string $id): void
    {
        $k = (new KamarService())->detail((int)$id);
        if ($k === null) {
            $this->notFound();
        }
        if ($k['status'] !== 'Tersedia') {
            flash('warn', 'Kamar ini sudah tidak tersedia.');
            $this->redirect('/kamar/' . (int)$id);
        }
        $this->view('pemesanan/form', ['title' => 'Pesan Kamar ' . $k['nomor_kamar'], 'k' => $k, 'est' => $this->svc->estimasi($k), 'dpPersen' => PemesananService::persenDp()]);
    }

    public function simpan(string $id): void
    {
        $idBaru = $this->svc->buat(Auth::id(), (int)$id, $_POST);
        flash('success', 'Pemesanan dibuat. Bayar DP dan unggah bukti dalam 24 jam agar kamar tetap tertahan.');
        $this->redirect('/pemesanan/' . $idBaru);
    }

    public function saya(): void
    {
        $this->view('pemesanan/saya', ['title' => 'Pemesanan Saya', 'daftar' => $this->svc->daftarPenyewa(Auth::id())]);
    }

    /** Ringkasan pemesanan, tagihan DP/pelunasan, dan UI-15 pembatalan. */
    public function show(string $id): void
    {
        $p = $this->svc->detail((int)$id);
        if ($p === null || (int)$p['id_penyewa'] !== Auth::id()) {
            $this->notFound();
        }
        $tagihan = new TagihanService();
        $bayar = new PembayaranService();
        $daftar = $tagihan->byPemesanan((int)$id);
        foreach ($daftar as &$t) {
            $t['bisa_unggah'] = $bayar->bolehUnggah($t);
        }
        unset($t);
        $this->view('pemesanan/show', ['title' => 'Pemesanan #' . $p['id_pemesanan'], 'p' => $p, 'tagihan' => $daftar]);
    }

    public function batal(string $id): void
    {
        $this->svc->batalkan(Auth::id(), (int)$id, $this->input('alasan'));
        flash('success', 'Pemesanan dibatalkan.');
        $this->redirect('/pemesanan/' . (int)$id);
    }

    public function akhiri(string $id): void
    {
        $this->svc->akhiriSewa((int)$id);
        flash('success', 'Sewa diakhiri. Kamar kembali Tersedia.');
        $this->redirect('/admin/kamar');
    }

    /** Putus sewa sepihak (admin) setelah melewati batas toleransi. */
    public function putus(string $id): void
    {
        $this->svc->putusSepihak((int)$id);
        flash('success', 'Sewa diputus sepihak. Kamar dikosongkan dan penyewa masuk daftar hitam.');
        $this->redirect('/admin/belum-bayar');
    }

    private function notFound(): never
    {
        http_response_code(404);
        View::render('errors/404', ['title' => 'Halaman tidak ditemukan']);
        exit;
    }
}
