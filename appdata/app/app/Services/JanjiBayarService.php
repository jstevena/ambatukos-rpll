<?php
declare(strict_types=1);

/** Janji Bayar (Promise to Pay): penyewa minta kelonggaran, admin menyetujui, denda dijeda sampai tanggal janji. */
class JanjiBayarService
{
    public const MAKS_HARI = 14;

    private JanjiBayarRepository $repo;
    private TagihanRepository $tagihan;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->repo = new JanjiBayarRepository();
        $this->tagihan = new TagihanRepository();
        $this->notif = new NotifikasiService();
    }

    public function menunggu(): array
    {
        return $this->repo->menunggu();
    }

    public function jumlahMenunggu(): int
    {
        return $this->repo->jumlahMenunggu();
    }

    public function ajukan(int $idPenyewa, int $idTagihan, string $tanggal, string $alasan): void
    {
        $t = $this->tagihan->detail($idTagihan);
        if ($t === null || (int)$t['id_penyewa'] !== $idPenyewa) {
            throw new AppException('Tagihan tidak ditemukan.');
        }
        if (!in_array($t['jenis'], ['Sewa Bulanan', 'Sewa Tahunan'], true)
            || !in_array($t['status'], ['Belum Dibayar', 'Terlambat'], true) || $t['status_pemesanan'] !== 'Aktif') {
            throw new AppException('Janji bayar hanya dapat diajukan untuk tagihan sewa yang belum dibayar.');
        }
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new AppException('Alasan wajib diisi.');
        }
        if (mb_strlen($alasan) > 255) {
            throw new AppException('Alasan maksimal 255 karakter.');
        }
        $d = DateTime::createFromFormat('Y-m-d', $tanggal);
        if ($d === false || $d->format('Y-m-d') !== $tanggal) {
            throw new AppException('Tanggal janji bayar tidak valid.');
        }
        if ($tanggal <= date('Y-m-d')) {
            throw new AppException('Tanggal janji bayar harus setelah hari ini.');
        }
        if ($tanggal > date('Y-m-d', strtotime('+' . self::MAKS_HARI . ' days'))) {
            throw new AppException('Tanggal janji bayar maksimal ' . self::MAKS_HARI . ' hari dari hari ini.');
        }
        Database::transaction(function () use ($idTagihan, $tanggal, $alasan) {
            $this->tagihan->find($idTagihan, true); // kunci agar tidak ada pengajuan ganda
            if ($this->repo->aktifByTagihan($idTagihan) !== null) {
                throw new AppException('Tagihan ini sudah memiliki janji bayar (diajukan atau disetujui).');
            }
            $this->repo->create([
                'id_tagihan' => $idTagihan, 'tanggal_janji' => $tanggal, 'alasan' => $alasan,
                'status' => 'Diajukan', 'tanggal_ajukan' => date('Y-m-d H:i:s'),
            ]);
        });
        $this->notif->kirimKeAdmin('janji_bayar', sprintf('%s mengajukan janji bayar tagihan kamar %s sampai %s.', $t['nama_penyewa'], $t['nomor_kamar'], tgl($tanggal)));
    }

    public function setujui(int $idAdmin, int $id): void
    {
        Database::transaction(function () use ($idAdmin, $id) {
            $j = $this->repo->find($id, true);
            if ($j === null || $j['status'] !== 'Diajukan') {
                throw new AppException('Pengajuan tidak ditemukan atau sudah diproses.');
            }
            $this->repo->update($id, ['status' => 'Disetujui', 'id_admin' => $idAdmin]);
            $t = $this->tagihan->detail((int)$j['id_tagihan']);
            $this->notif->kirim((int)$t['id_penyewa'], 'janji_bayar', sprintf(
                'Janji bayar disetujui sampai %s. Denda dijeda sampai tanggal itu; jika belum lunas, denda aktif kembali.', tgl($j['tanggal_janji'])
            ));
        });
    }

    public function tolak(int $idAdmin, int $id, string $catatan): void
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new AppException('Alasan penolakan wajib diisi.');
        }
        Database::transaction(function () use ($idAdmin, $id, $catatan) {
            $j = $this->repo->find($id, true);
            if ($j === null || $j['status'] !== 'Diajukan') {
                throw new AppException('Pengajuan tidak ditemukan atau sudah diproses.');
            }
            $this->repo->update($id, ['status' => 'Ditolak', 'id_admin' => $idAdmin, 'catatan_admin' => mb_substr($catatan, 0, 255)]);
            $t = $this->tagihan->detail((int)$j['id_tagihan']);
            $this->notif->kirim((int)$t['id_penyewa'], 'janji_bayar', 'Janji bayar ditolak: ' . $catatan . '. Denda tetap berjalan sesuai aturan.');
        });
    }
}
