<?php
declare(strict_types=1);

/** UC-13 Ajukan Keluhan / Perbaikan. */
class KeluhanService
{
    private KeluhanRepository $repo;
    private PemesananRepository $pemesanan;
    private KamarRepository $kamar;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->repo = new KeluhanRepository();
        $this->pemesanan = new PemesananRepository();
        $this->kamar = new KamarRepository();
        $this->notif = new NotifikasiService();
    }

    public function semua(): array
    {
        return $this->repo->semua();
    }

    public function byPenyewa(int $idPenyewa): array
    {
        return $this->repo->byPenyewa($idPenyewa);
    }

    public function kamarSewaan(int $idPenyewa): array
    {
        return $this->pemesanan->aktifByPenyewa($idPenyewa);
    }

    public function ajukan(int $idPenyewa, int $idKamar, string $judul, string $deskripsi): void
    {
        if ((new TagihanService())->menunggak($idPenyewa) !== null) {
            throw new AppException('Fitur keluhan dikunci sementara karena tagihan Anda menunggak. Lunasi tagihan terlebih dahulu.');
        }
        $judul = trim($judul);
        $deskripsi = trim($deskripsi);
        if ($judul === '' || $deskripsi === '') {
            throw new AppException('Judul dan deskripsi keluhan wajib diisi.');
        }
        if (mb_strlen($judul) > 100) {
            throw new AppException('Judul maksimal 100 karakter.');
        }
        $sewa = array_filter($this->pemesanan->aktifByPenyewa($idPenyewa), static fn($p) => (int)$p['id_kamar'] === $idKamar);
        if ($sewa === []) {
            throw new AppException('Keluhan hanya dapat diajukan untuk kamar yang sedang Anda sewa.');
        }
        $kamar = $this->kamar->find($idKamar);
        $this->repo->create([
            'id_penyewa' => $idPenyewa, 'id_kamar' => $idKamar, 'id_admin' => null, 'judul' => $judul,
            'deskripsi' => $deskripsi, 'status' => 'Baru', 'tanggal_lapor' => date('Y-m-d H:i:s'),
        ]);
        $this->notif->kirimKeAdmin('keluhan', sprintf('Keluhan baru kamar %s: %s', $kamar['nomor_kamar'], $judul));
    }

    public function ubahStatus(int $idAdmin, int $id, string $status): void
    {
        if (!in_array($status, ['Baru', 'Diproses', 'Selesai'], true)) {
            throw new AppException('Status tidak valid.');
        }
        $k = $this->repo->find($id);
        if ($k === null) {
            throw new AppException('Keluhan tidak ditemukan.');
        }
        $this->repo->update($id, ['status' => $status, 'id_admin' => $idAdmin]);
        $this->notif->kirim((int)$k['id_penyewa'], 'keluhan', sprintf('Status keluhan "%s" kini: %s.', $k['judul'], $status));
    }
}
