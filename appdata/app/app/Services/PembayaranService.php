<?php
declare(strict_types=1);

/** UC-05, UC-06, UC-07, UC-10 dan pengembalian DP pada UC-15. */
class PembayaranService
{
    private PembayaranRepository $pembayaran;
    private TagihanRepository $tagihan;
    private PemesananRepository $pemesanan;
    private KamarRepository $kamar;
    private KamarService $kamarSvc;
    private FileStorageHandler $file;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->pembayaran = new PembayaranRepository();
        $this->tagihan = new TagihanRepository();
        $this->pemesanan = new PemesananRepository();
        $this->kamar = new KamarRepository();
        $this->kamarSvc = new KamarService();
        $this->file = new FileStorageHandler();
        $this->notif = new NotifikasiService();
    }

    /** UC-06 Upload Bukti Pembayaran. */
    public function unggah(int $idPenyewa, int $idTagihan, array $berkas): void
    {
        $t = $this->tagihan->detail($idTagihan);
        if ($t === null || (int)$t['id_penyewa'] !== $idPenyewa) {
            throw new AppException('Tagihan tidak ditemukan.');
        }
        $this->pastikanBisaUnggah($t);
        $path = $this->file->simpan($berkas, 'bukti');
        try {
            Database::transaction(function () use ($idTagihan, $path) {
                $cur = $this->tagihan->find($idTagihan, true);
                if ($cur === null || !in_array($cur['status'], ['Belum Dibayar', 'Terlambat'], true)) {
                    throw new AppException('Tagihan ini sudah tidak dapat diunggah buktinya.');
                }
                $this->pembayaran->create([
                    'id_tagihan' => $idTagihan, 'tanggal_bayar' => date('Y-m-d H:i:s'), 'jumlah' => $cur['jumlah'],
                    'bukti_transfer' => $path, 'status' => 'Menunggu Verifikasi',
                ]);
                $this->tagihan->update($idTagihan, ['status' => 'Menunggu Verifikasi']);
            });
        } catch (Throwable $e) {
            $this->file->hapus($path);
            throw $e;
        }
        $this->notif->kirimKeAdmin('verifikasi', sprintf('Bukti pembayaran %s kamar %s dari %s menunggu verifikasi.', $t['jenis'], $t['nomor_kamar'], $t['nama_penyewa']));
    }

    private function pastikanBisaUnggah(array $t): void
    {
        if (!in_array($t['status'], ['Belum Dibayar', 'Terlambat'], true)) {
            throw new AppException('Tagihan ini tidak dapat diunggah buktinya (status: ' . $t['status'] . ').');
        }
        if ($t['jenis'] === 'DP' && strtotime($t['jatuh_tempo']) < time()) {
            throw new AppException('Batas pembayaran DP 24 jam telah lewat. Silakan melakukan pemesanan ulang.');
        }
    }

    public function bolehUnggah(array $tagihan): bool
    {
        try {
            $this->pastikanBisaUnggah($tagihan);
            return true;
        } catch (AppException) {
            return false;
        }
    }

    /** UC-07 Setujui: tindak lanjut bergantung pada jenis tagihan (SD-03). */
    public function setujui(int $idPembayaran, int $idAdmin): void
    {
        Database::transaction(function () use ($idPembayaran, $idAdmin) {
            $pb = $this->pembayaran->find($idPembayaran, true);
            if ($pb === null || $pb['status'] !== 'Menunggu Verifikasi') {
                throw new AppException('Pembayaran ini tidak dalam status menunggu verifikasi.');
            }
            $t = $this->tagihan->find((int)$pb['id_tagihan'], true);
            $p = $this->pemesanan->find((int)$t['id_pemesanan'], true);
            $now = date('Y-m-d H:i:s');

            $this->pembayaran->update($idPembayaran, ['status' => 'Disetujui', 'id_admin' => $idAdmin, 'tanggal_verifikasi' => $now]);
            $this->tagihan->update((int)$t['id_tagihan'], ['status' => 'Lunas']);
            $kamar = $this->kamar->find((int)$p['id_kamar']);
            $idPenyewa = (int)$p['id_penyewa'];

            if ($t['jenis'] === 'DP') {
                if ($p['status'] !== 'Menunggu DP') {
                    throw new AppException('Pemesanan sudah tidak menunggu DP.');
                }
                $this->pemesanan->update((int)$p['id_pemesanan'], ['status' => 'Menunggu Pelunasan']);
                // BR-04: pelunasan = biaya periode pertama - DP, jatuh tempo pada tanggal masuk.
                $biaya = $this->kamarSvc->biayaEfektif($kamar, $p['tanggal_masuk']);
                $sisa = max(0.0, round($biaya - (float)$t['jumlah'], 2));
                $this->tagihan->create([
                    'id_pemesanan' => (int)$p['id_pemesanan'], 'jenis' => 'Pelunasan', 'periode_ke' => null, 'jumlah' => $sisa,
                    'tanggal_terbit' => $now, 'jatuh_tempo' => $p['tanggal_masuk'] . ' 23:59:59',
                    'status' => 'Belum Dibayar', 'pengingat_terkirim' => 0,
                ]);
                $this->notif->kirim($idPenyewa, 'verifikasi', sprintf(
                    'DP kamar %s disetujui. Kunjungi kamar pada %s; jika sudah OK, serahkan fotokopi KTP, lalu lunasi %s paling lambat %s.',
                    $kamar['nomor_kamar'], tgl($p['tanggal_kunjungan']), rupiah($sisa), tgl($p['tanggal_masuk'])
                ));
            } elseif ($t['jenis'] === 'Pelunasan') {
                if ($p['status'] !== 'Menunggu Pelunasan') {
                    throw new AppException('Pemesanan sudah tidak menunggu pelunasan.');
                }
                // BR-07: aktivasi sewa.
                $this->pemesanan->update((int)$p['id_pemesanan'], ['status' => 'Aktif', 'tanggal_mulai_sewa' => $p['tanggal_masuk']]);
                $this->kamar->setStatus((int)$p['id_kamar'], 'Terisi');
                $this->notif->kirim($idPenyewa, 'verifikasi', sprintf(
                    'Pelunasan disetujui. Sewa kamar %s aktif sejak %s. Selamat datang!', $kamar['nomor_kamar'], tgl($p['tanggal_masuk'])
                ));
            } else {
                // BR-10: pembayaran terlambat tetap Lunas dan tidak menggeser jadwal.
                $this->notif->kirim($idPenyewa, 'verifikasi', sprintf(
                    'Pembayaran %s kamar %s sebesar %s disetujui. Terima kasih.', $t['jenis'], $kamar['nomor_kamar'], rupiah($pb['jumlah'])
                ));
            }
        });
    }

    public function tolak(int $idPembayaran, int $idAdmin, string $catatan): void
    {
        $catatan = trim($catatan);
        if ($catatan === '') {
            throw new AppException('Alasan penolakan wajib diisi.');
        }
        Database::transaction(function () use ($idPembayaran, $idAdmin, $catatan) {
            $pb = $this->pembayaran->find($idPembayaran, true);
            if ($pb === null || $pb['status'] !== 'Menunggu Verifikasi') {
                throw new AppException('Pembayaran ini tidak dalam status menunggu verifikasi.');
            }
            $t = $this->tagihan->detail((int)$pb['id_tagihan']);
            $this->pembayaran->update($idPembayaran, [
                'status' => 'Ditolak', 'id_admin' => $idAdmin, 'tanggal_verifikasi' => date('Y-m-d H:i:s'),
                'catatan_admin' => mb_substr($catatan, 0, 255),
            ]);
            $this->tagihan->update((int)$pb['id_tagihan'], ['status' => 'Belum Dibayar']);
            $this->notif->kirim((int)$t['id_penyewa'], 'verifikasi', sprintf(
                'Bukti pembayaran %s kamar %s ditolak: %s. Silakan unggah ulang.', $t['jenis'], $t['nomor_kamar'], $catatan
            ));
        });
    }

    /** UC-15 sisi admin: DP dikembalikan, status pembayaran menjadi Dikembalikan. */
    public function kembalikanDp(int $idPembayaran, int $idAdmin, string $catatan): void
    {
        Database::transaction(function () use ($idPembayaran, $idAdmin, $catatan) {
            $pb = $this->pembayaran->find($idPembayaran, true);
            if ($pb === null || $pb['status'] !== 'Menunggu Pengembalian') {
                throw new AppException('Tidak ada pengembalian DP yang menunggu untuk pembayaran ini.');
            }
            $d = $this->pembayaran->detail($idPembayaran);
            $catatan = trim($catatan);
            $this->pembayaran->update($idPembayaran, [
                'status' => 'Dikembalikan', 'id_admin' => $idAdmin,
                'catatan_admin' => $catatan !== '' ? mb_substr($catatan, 0, 255) : 'DP dikembalikan penuh.',
            ]);
            $this->notif->kirim((int)$d['id_penyewa'], 'pengembalian_dp', sprintf('DP %s untuk kamar %s telah dikembalikan.', rupiah($pb['jumlah']), $d['nomor_kamar']));
        });
    }

    public function menungguVerifikasi(): array
    {
        return $this->pembayaran->menungguVerifikasi();
    }

    public function jumlahMenungguVerifikasi(): int
    {
        return $this->pembayaran->jumlahMenungguVerifikasi();
    }

    public function menungguPengembalian(): array
    {
        return $this->pembayaran->menungguPengembalian();
    }

    public function detail(int $id): ?array
    {
        return $this->pembayaran->detail($id);
    }

    /** UC-10 Lihat Penyewa Belum Bayar. */
    public function belumBayar(): array
    {
        return $this->tagihan->belumBayar(date('Y-m-d H:i:s'));
    }

    public function jumlahPenyewaBelumBayar(): int
    {
        return $this->tagihan->jumlahPenyewaBelumBayar(date('Y-m-d H:i:s'));
    }

    /** NFR-02: bukti hanya dapat dibuka penyewa pemilik tagihan, admin, dan pemilik kos. */
    public function bolehLihatBukti(array $user, array $pembayaran): bool
    {
        return in_array($user['peran'], ['admin', 'pemilik'], true)
            || (int)$pembayaran['id_penyewa'] === (int)$user['id_pengguna'];
    }
}
