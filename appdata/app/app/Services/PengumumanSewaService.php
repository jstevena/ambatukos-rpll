<?php
declare(strict_types=1);

/** UC-14 Tetapkan Kenaikan Sewa (pemilik) dan UC-09 Umumkan Kenaikan Sewa (admin); BR-12. */
class PengumumanSewaService
{
    public const MIN_HARI = 30;

    private PengumumanSewaRepository $repo;
    private KamarRepository $kamar;
    private PemesananRepository $pemesanan;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->repo = new PengumumanSewaRepository();
        $this->kamar = new KamarRepository();
        $this->pemesanan = new PemesananRepository();
        $this->notif = new NotifikasiService();
    }

    public function daftar(): array
    {
        return $this->repo->daftar();
    }

    public function jumlahDitetapkan(): int
    {
        return $this->repo->jumlahDitetapkan();
    }

    public function tetapkan(int $idPemilik, int $idKamar, string $biaya, string $berlaku): int
    {
        $kamar = $this->kamar->find($idKamar);
        if ($kamar === null) {
            throw new AppException('Pilih kamar yang valid.');
        }
        if (!ctype_digit($biaya) || (float)$biaya <= 0) {
            throw new AppException('Biaya baru harus berupa angka lebih dari 0.');
        }
        $d = DateTime::createFromFormat('Y-m-d', $berlaku);
        if ($d === false || $d->format('Y-m-d') !== $berlaku) {
            throw new AppException('Tanggal berlaku tidak valid.');
        }
        // Skenario usability #5: form menolak tanggal berlaku kurang dari 30 hari setelah pengumuman.
        $this->pastikanMinimal30Hari($berlaku);

        $id = $this->repo->create([
            'id_kamar' => $idKamar, 'id_pemilik' => $idPemilik, 'id_admin' => null,
            'biaya_baru' => $biaya, 'tanggal_berlaku' => $berlaku, 'status' => 'Ditetapkan',
        ]);
        $this->notif->kirimKeAdmin('kenaikan_sewa', sprintf(
            'Pemilik menetapkan kenaikan sewa kamar %s menjadi %s mulai %s. Mohon diumumkan.',
            $kamar['nomor_kamar'], rupiah($biaya), tgl($berlaku)
        ));
        return $id;
    }

    public function umumkan(int $idAdmin, int $idPengumuman): int
    {
        return (int)Database::transaction(function () use ($idAdmin, $idPengumuman) {
            $s = $this->repo->find($idPengumuman, true);
            if ($s === null || $s['status'] !== 'Ditetapkan') {
                throw new AppException('Penetapan tidak ditemukan atau sudah diumumkan.');
            }
            // UC-09 alternative flow: tanggal berlaku kurang dari 30 hari.
            $this->pastikanMinimal30Hari($s['tanggal_berlaku'], 'Penetapan ini tidak dapat diumumkan: ');
            $this->repo->update($idPengumuman, ['status' => 'Diumumkan', 'id_admin' => $idAdmin]);
            $kamar = $this->kamar->find((int)$s['id_kamar']);
            $dikirim = 0;
            foreach ($this->pemesanan->aktifByKamar((int)$s['id_kamar']) as $p) {
                $this->notif->kirim((int)$p['id_penyewa'], 'kenaikan_sewa', sprintf(
                    'Biaya sewa kamar %s naik menjadi %s per bulan mulai %s (berlaku untuk tagihan yang jatuh tempo pada/setelah tanggal tersebut).',
                    $kamar['nomor_kamar'], rupiah($s['biaya_baru']), tgl($s['tanggal_berlaku'])
                ));
                $dikirim++;
            }
            return $dikirim; // 0 = kamar tanpa penyewa aktif (alternative flow)
        });
    }

    private function pastikanMinimal30Hari(string $berlaku, string $prefix = ''): void
    {
        $min = date('Y-m-d', strtotime('+' . self::MIN_HARI . ' days'));
        if ($berlaku < $min) {
            throw new AppException($prefix . 'Tanggal berlaku minimal ' . self::MIN_HARI . ' hari setelah pengumuman (paling cepat ' . tgl($min) . ').');
        }
    }
}
