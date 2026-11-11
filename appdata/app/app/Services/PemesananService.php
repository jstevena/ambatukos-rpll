<?php
declare(strict_types=1);

/** UC-04 Pesan Kamar, UC-15 Batalkan Pemesanan, serta penutupan pemesanan oleh Penjadwal (BR-01, BR-05, BR-06). */
class PemesananService
{
    private PemesananRepository $pemesanan;
    private KamarRepository $kamar;
    private TagihanRepository $tagihan;
    private PembayaranRepository $pembayaran;
    private KamarService $kamarSvc;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->pemesanan = new PemesananRepository();
        $this->kamar = new KamarRepository();
        $this->tagihan = new TagihanRepository();
        $this->pembayaran = new PembayaranRepository();
        $this->kamarSvc = new KamarService();
        $this->notif = new NotifikasiService();
    }

    public function daftarPenyewa(int $idPenyewa): array
    {
        return $this->pemesanan->byPenyewa($idPenyewa);
    }

    public function aktifPenyewa(int $idPenyewa): array
    {
        return $this->pemesanan->aktifByPenyewa($idPenyewa);
    }

    public function detail(int $id): ?array
    {
        return $this->pemesanan->detail($id);
    }

    public static function persenDp(): float
    {
        $p = (float)env('DP_PERSEN', '30');
        return ($p > 0 && $p < 100) ? $p : 30.0;
    }

    /** Estimasi untuk ditampilkan pada form (BR-02, BR-04). */
    public function estimasi(array $kamar, ?string $tanggalMasuk = null): array
    {
        $biaya = $this->kamarSvc->biayaEfektif($kamar, $tanggalMasuk ?: date('Y-m-d'));
        $dp = round($biaya * self::persenDp() / 100, 2);
        return ['biaya' => $biaya, 'dp' => $dp, 'pelunasan' => round($biaya - $dp, 2)];
    }

    public function buat(int $idPenyewa, int $idKamar, array $in): int
    {
        $kunjungan = $this->tanggal((string)($in['tanggal_kunjungan'] ?? ''), 'Tanggal kunjungan');
        $masuk = $this->tanggal((string)($in['tanggal_masuk'] ?? ''), 'Tanggal masuk');
        $hariIni = date('Y-m-d');
        if ($kunjungan < $hariIni) {
            throw new AppException('Tanggal kunjungan tidak boleh sebelum hari ini.');
        }
        if ($kunjungan > $masuk) {
            throw new AppException('Tanggal kunjungan harus sebelum atau sama dengan tanggal masuk.');
        }
        $jenis = (string)($in['jenis_sewa'] ?? '');
        if (!in_array($jenis, ['tetap', 'sementara'], true)) {
            throw new AppException('Pilih jenis sewa.');
        }
        $durasi = null;
        if ($jenis === 'sementara') {
            $durasi = (int)($in['durasi_bulan'] ?? 0);
            if ($durasi < 1 || $durasi > 60) {
                throw new AppException('Durasi sewa sementara harus 1 sampai 60 bulan.');
            }
        }

        return Database::transaction(function () use ($idPenyewa, $idKamar, $kunjungan, $masuk, $jenis, $durasi) {
            // Cek ketersediaan + ubah status dalam satu transaksi dengan penguncian baris kamar.
            $kamar = $this->kamar->findForUpdate($idKamar);
            if ($kamar === null) {
                throw new AppException('Kamar tidak ditemukan.');
            }
            if ($kamar['status'] !== 'Tersedia') {
                throw new AppException('Kamar sudah dipesan pihak lain.');
            }
            $now = date('Y-m-d H:i:s');
            $batas = date('Y-m-d H:i:s', strtotime('+24 hours'));
            $est = $this->estimasi($kamar, $masuk);

            $id = $this->pemesanan->create([
                'id_penyewa' => $idPenyewa, 'id_kamar' => $idKamar, 'tanggal_pesan' => $now, 'batas_bayar_dp' => $batas,
                'tanggal_kunjungan' => $kunjungan, 'tanggal_masuk' => $masuk, 'jenis_sewa' => $jenis,
                'durasi_bulan' => $durasi, 'status' => 'Menunggu DP',
            ]);
            $this->kamar->setStatus($idKamar, 'Dipesan');
            $this->tagihan->create([
                'id_pemesanan' => $id, 'jenis' => 'DP', 'periode_ke' => null, 'jumlah' => $est['dp'],
                'tanggal_terbit' => $now, 'jatuh_tempo' => $batas, 'status' => 'Belum Dibayar', 'pengingat_terkirim' => 0,
            ]);
            $this->notif->kirim($idPenyewa, 'tagihan', sprintf(
                'Tagihan DP kamar %s sebesar %s terbit. Bayar dan unggah bukti sebelum %s.',
                $kamar['nomor_kamar'], rupiah($est['dp']), tgl($batas, true)
            ));
            return $id;
        });
    }

    /** UC-15 sisi penyewa. */
    public function batalkan(int $idPenyewa, int $id, string $alasan): void
    {
        $alasan = trim($alasan);
        if ($alasan === '') {
            throw new AppException('Alasan pembatalan wajib diisi.');
        }
        Database::transaction(function () use ($idPenyewa, $id, $alasan) {
            $p = $this->pemesanan->find($id, true);
            if ($p === null || (int)$p['id_penyewa'] !== $idPenyewa) {
                throw new AppException('Pemesanan tidak ditemukan.');
            }
            if (!in_array($p['status'], ['Menunggu DP', 'Menunggu Pelunasan'], true)) {
                throw new AppException('Pemesanan ini tidak dapat dibatalkan.');
            }
            if ($p['status'] === 'Menunggu Pelunasan' && date('Y-m-d') > $p['tanggal_masuk']) {
                throw new AppException('Batas pelunasan sudah lewat; pemesanan akan ditutup otomatis oleh sistem.');
            }
            $pel = $this->tagihan->findByJenis($id, 'Pelunasan');
            if ($pel !== null && $pel['status'] === 'Menunggu Verifikasi') {
                throw new AppException('Pelunasan Anda sedang diverifikasi admin, pembatalan belum dapat dilakukan.');
            }

            $this->pemesanan->update($id, ['status' => 'Dibatalkan', 'alasan_batal' => mb_substr($alasan, 0, 255)]);
            $this->kamar->setStatus((int)$p['id_kamar'], 'Tersedia');
            foreach ($this->tagihan->byPemesanan($id) as $t) {
                if (in_array($t['status'], ['Belum Dibayar', 'Terlambat', 'Menunggu Verifikasi'], true)) {
                    $this->tagihan->update((int)$t['id_tagihan'], ['status' => 'Dibatalkan']);
                }
            }
            // BR-05: DP yang sudah dibayar dikembalikan penuh oleh admin.
            $dp = $this->pembayaran->dpAktif($id);
            if ($dp !== null && in_array($dp['status'], ['Disetujui', 'Menunggu Verifikasi'], true)) {
                $this->pembayaran->update((int)$dp['id_pembayaran'], [
                    'status' => 'Menunggu Pengembalian', 'catatan_admin' => 'Dibatalkan penyewa: ' . mb_substr($alasan, 0, 200),
                ]);
                $this->notif->kirimKeAdmin('pengembalian_dp', sprintf('Pemesanan #%d dibatalkan. Kembalikan DP %s ke penyewa.', $id, rupiah($dp['jumlah'])));
            }
            $this->notif->kirim($idPenyewa, 'pengembalian_dp', $dp !== null
                ? 'Pemesanan dibatalkan. DP Anda akan dikembalikan penuh oleh admin.'
                : 'Pemesanan dibatalkan dan kamar kembali tersedia.');
        });
    }

    /** Admin mengakhiri sewa (penyewa keluar). Kamar kembali Tersedia. */
    public function akhiriSewa(int $id): void
    {
        Database::transaction(function () use ($id) {
            $p = $this->pemesanan->find($id, true);
            if ($p === null || $p['status'] !== 'Aktif') {
                throw new AppException('Sewa aktif tidak ditemukan.');
            }
            if ($this->tagihan->belumLunasByPemesanan($id) > 0) {
                throw new AppException('Masih ada tagihan yang belum lunas. Selesaikan terlebih dahulu.');
            }
            $this->selesaikan($p);
        });
    }

    private function selesaikan(array $p): void
    {
        $this->pemesanan->update((int)$p['id_pemesanan'], ['status' => 'Selesai']);
        $this->kamar->setStatus((int)$p['id_kamar'], 'Tersedia');
        $this->notif->kirim((int)$p['id_penyewa'], 'tagihan', 'Masa sewa Anda telah berakhir. Terima kasih telah menyewa di AmbatuKos.');
    }

    // ---------- Dipanggil Penjadwal (UC-11) ----------

    public function kedaluwarsakanDp(): int
    {
        $n = 0;
        foreach ($this->pemesanan->dpKedaluwarsa(date('Y-m-d H:i:s')) as $row) {
            $id = (int)$row['id_pemesanan'];
            $n += (int)Database::transaction(function () use ($id) {
                $p = $this->pemesanan->find($id, true);
                $dp = $this->tagihan->findByJenis($id, 'DP');
                if ($p === null || $p['status'] !== 'Menunggu DP' || $dp === null || $dp['status'] !== 'Belum Dibayar') {
                    return 0;
                }
                $this->pemesanan->update($id, ['status' => 'Kedaluwarsa']);
                $this->tagihan->update((int)$dp['id_tagihan'], ['status' => 'Dibatalkan']);
                $this->kamar->setStatus((int)$p['id_kamar'], 'Tersedia');
                $this->notif->kirim((int)$p['id_penyewa'], 'tagihan', 'Pemesanan kedaluwarsa karena DP tidak dibayar dalam 24 jam. Kamar kembali tersedia.');
                return 1;
            });
        }
        return $n;
    }

    public function kedaluwarsakanPelunasan(): int
    {
        $n = 0;
        foreach ($this->pemesanan->pelunasanKedaluwarsa(date('Y-m-d H:i:s')) as $row) {
            $id = (int)$row['id_pemesanan'];
            $n += (int)Database::transaction(function () use ($id) {
                $p = $this->pemesanan->find($id, true);
                $pel = $this->tagihan->findByJenis($id, 'Pelunasan');
                if ($p === null || $p['status'] !== 'Menunggu Pelunasan' || $pel === null || $pel['status'] === 'Menunggu Verifikasi' || $pel['status'] === 'Lunas') {
                    return 0;
                }
                $this->pemesanan->update($id, ['status' => 'Kedaluwarsa']);
                $this->tagihan->update((int)$pel['id_tagihan'], ['status' => 'Dibatalkan']);
                $this->kamar->setStatus((int)$p['id_kamar'], 'Tersedia');
                $dp = $this->pembayaran->dpAktif($id);
                if ($dp !== null && $dp['status'] === 'Disetujui') {
                    $this->pembayaran->update((int)$dp['id_pembayaran'], ['status' => 'Hangus', 'catatan_admin' => 'Pelunasan tidak dibayar sampai tanggal masuk (BR-06).']);
                }
                $this->notif->kirim((int)$p['id_penyewa'], 'tagihan', 'Pemesanan kedaluwarsa karena pelunasan tidak dibayar sampai tanggal masuk. DP berstatus hangus.');
                return 1;
            });
        }
        return $n;
    }

    /** Sewa sementara berakhir pada tanggal mulai + durasi bulan (BR-11). */
    public function selesaikanSewaSementara(): int
    {
        $tagihanSvc = new TagihanService();
        $n = 0;
        foreach ($this->pemesanan->aktifSementara() as $p) {
            $akhir = $tagihanSvc->hitungJatuhTempo((string)$p['tanggal_mulai_sewa'], (int)$p['durasi_bulan']);
            if (date('Y-m-d') >= $akhir) {
                Database::transaction(fn() => $this->selesaikan($p));
                $n++;
            }
        }
        return $n;
    }

    private function tanggal(string $v, string $label): string
    {
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if ($d === false || $d->format('Y-m-d') !== $v) {
            throw new AppException("$label tidak valid.");
        }
        return $v;
    }
}
