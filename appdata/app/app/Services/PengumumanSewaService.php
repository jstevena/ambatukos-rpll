<?php
declare(strict_types=1);

/**
 * Kenaikan sewa (BR-12). Pemilik menetapkan dan boleh langsung mengumumkan;
 * admin juga dapat mengumumkan penetapan yang belum diumumkan.
 */
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

    /**
     * @return int|null null = hanya ditetapkan (menunggu diumumkan);
     *                  int  = sudah diumumkan, nilainya jumlah penyewa yang diberi notifikasi.
     */
    public function tetapkan(int $idPemilik, int $idKamar, string $biaya, string $berlaku, bool $langsung = false): ?int
    {
        $kamar = $this->kamar->find($idKamar);
        if ($kamar === null) {
            throw new AppException('Pilih kamar yang valid.');
        }
        $biaya = normalisasi_rupiah($biaya);
        if (!ctype_digit($biaya) || (float)$biaya <= 0 || strlen($biaya) > 10) {
            throw new AppException('Biaya baru harus berupa angka lebih dari 0.');
        }
        $d = DateTime::createFromFormat('Y-m-d', $berlaku);
        if ($d === false || $d->format('Y-m-d') !== $berlaku) {
            throw new AppException('Tanggal berlaku tidak valid.');
        }
        $this->pastikanMinimal30Hari($berlaku);

        return Database::transaction(function () use ($idPemilik, $idKamar, $biaya, $berlaku, $langsung, $kamar) {
            $id = $this->repo->create([
                'id_kamar' => $idKamar, 'id_pemilik' => $idPemilik, 'id_admin' => null,
                'biaya_baru' => $biaya, 'tanggal_berlaku' => $berlaku, 'status' => 'Ditetapkan',
            ]);
            if ($langsung) {
                return $this->terapkan($this->repo->find($id, true), $idPemilik, 'pemilik');
            }
            $this->notif->kirimKeAdmin('kenaikan_sewa', sprintf(
                'Pemilik menetapkan kenaikan sewa kamar %s menjadi %s mulai %s. Mohon diumumkan.',
                $kamar['nomor_kamar'], rupiah($biaya), tgl($berlaku)
            ));
            return null;
        });
    }

    /** Umumkan penetapan yang berstatus Ditetapkan. @return int jumlah penyewa yang diberi notifikasi */
    public function umumkan(int $idAktor, string $peranAktor, int $idPengumuman): int
    {
        return (int)Database::transaction(function () use ($idAktor, $peranAktor, $idPengumuman) {
            $s = $this->repo->find($idPengumuman, true);
            if ($s === null || $s['status'] !== 'Ditetapkan') {
                throw new AppException('Penetapan tidak ditemukan atau sudah diumumkan.');
            }
            return $this->terapkan($s, $idAktor, $peranAktor);
        });
    }

    private function terapkan(array $s, int $idAktor, string $peranAktor): int
    {
        $this->pastikanMinimal30Hari($s['tanggal_berlaku'], 'Penetapan ini tidak dapat diumumkan: ');
        // id_admin hanya diisi bila yang mengumumkan admin; NULL = diumumkan langsung oleh pemilik.
        $this->repo->update((int)$s['id_pengumuman'], ['status' => 'Diumumkan', 'id_admin' => $peranAktor === 'admin' ? $idAktor : null]);
        $kamar = $this->kamar->find((int)$s['id_kamar']);
        $dikirim = 0;
        foreach ($this->pemesanan->aktifByKamar((int)$s['id_kamar']) as $p) {
            $this->notif->kirim((int)$p['id_penyewa'], 'kenaikan_sewa', sprintf(
                'Biaya sewa kamar %s naik menjadi %s per bulan mulai %s (berlaku untuk tagihan yang jatuh tempo pada/setelah tanggal tersebut).',
                $kamar['nomor_kamar'], rupiah($s['biaya_baru']), tgl($s['tanggal_berlaku'])
            ));
            $dikirim++;
        }
        if ($peranAktor === 'pemilik') {
            $this->notif->kirimKeAdmin('kenaikan_sewa', sprintf(
                'Pemilik mengumumkan kenaikan sewa kamar %s menjadi %s mulai %s.',
                $kamar['nomor_kamar'], rupiah($s['biaya_baru']), tgl($s['tanggal_berlaku'])
            ));
        }
        return $dikirim; // 0 = kamar tanpa penyewa aktif
    }

    private function pastikanMinimal30Hari(string $berlaku, string $prefix = ''): void
    {
        $min = date('Y-m-d', strtotime('+' . self::MIN_HARI . ' days'));
        if ($berlaku < $min) {
            throw new AppException($prefix . 'Tanggal berlaku minimal ' . self::MIN_HARI . ' hari setelah pengumuman (paling cepat ' . tgl($min) . ').');
        }
    }
}
