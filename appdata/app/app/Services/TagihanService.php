<?php
declare(strict_types=1);

/** UC-11 Terbitkan Tagihan dan Notifikasi Jatuh Tempo (BR-08, BR-09, BR-10, BR-11, BR-12). */
class TagihanService
{
    private TagihanRepository $tagihan;
    private PemesananRepository $pemesanan;
    private KamarRepository $kamar;
    private KamarService $kamarSvc;
    private NotifikasiService $notif;

    public function __construct()
    {
        $this->tagihan = new TagihanRepository();
        $this->pemesanan = new PemesananRepository();
        $this->kamar = new KamarRepository();
        $this->kamarSvc = new KamarService();
        $this->notif = new NotifikasiService();
    }

    /**
     * BR-08: jatuh tempo = tanggal mulai sewa + n bulan (atau n tahun), selalu dihitung dari tanggal mulai sewa.
     * Bila bulan tujuan tidak memiliki tanggal yang sama, dipakai hari terakhir bulan itu.
     */
    public function hitungJatuhTempo(string $tanggalMulai, int $n, bool $tahunan = false): string
    {
        $tambahBulan = $tahunan ? $n * 12 : $n;
        $d = new DateTimeImmutable($tanggalMulai);
        $hari = (int)$d->format('j');
        $bulan = (int)$d->format('n') + $tambahBulan;
        $tahun = (int)$d->format('Y') + intdiv($bulan - 1, 12);
        $bulan = (($bulan - 1) % 12) + 1;
        $terakhir = (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $tahun, $bulan)))->format('t');
        return sprintf('%04d-%02d-%02d', $tahun, $bulan, min($hari, $terakhir));
    }

    /** BR-09: tagihan periode berikutnya terbit otomatis H-7 sebelum jatuh tempo. */
    public function terbitkanPeriodik(): int
    {
        $terbit = 0;
        $hariIni = date('Y-m-d');
        foreach ($this->pemesanan->aktif() as $p) {
            $idP = (int)$p['id_pemesanan'];
            $kamar = $this->kamar->find((int)$p['id_kamar']);
            if ($kamar === null || empty($p['tanggal_mulai_sewa'])) {
                continue;
            }
            // Periode 1 sudah dibayar lewat DP + pelunasan; periodik dimulai dari periode 2.
            $n = $this->tagihan->maxPeriode($idP) + 1;
            while ($n <= 600) {
                // BR-11: sewa sementara tidak ditagih melewati masa sewa.
                if ($p['jenis_sewa'] === 'sementara' && $n > (int)$p['durasi_bulan']) {
                    break;
                }
                $jt = $this->hitungJatuhTempo((string)$p['tanggal_mulai_sewa'], $n - 1);
                if ($hariIni < date('Y-m-d', strtotime($jt . ' -7 days'))) {
                    break;
                }
                if (!$this->tagihan->exists($idP, 'Sewa Bulanan', $n)) {
                    $jumlah = $this->kamarSvc->biayaEfektif($kamar, $jt); // BR-12
                    try {
                        $this->tagihan->create([
                            'id_pemesanan' => $idP, 'jenis' => 'Sewa Bulanan', 'periode_ke' => $n, 'jumlah' => $jumlah,
                            'tanggal_terbit' => date('Y-m-d H:i:s'), 'jatuh_tempo' => $jt . ' 23:59:59',
                            'status' => 'Belum Dibayar', 'pengingat_terkirim' => 0,
                        ]);
                    } catch (PDOException $e) {
                        if ($e->getCode() !== '23000') { // 23000 = sudah diterbitkan (kunci unik)
                            throw $e;
                        }
                        $n++;
                        continue;
                    }
                    $this->notif->kirim((int)$p['id_penyewa'], 'tagihan', sprintf(
                        'Tagihan sewa periode %d kamar %s sebesar %s terbit, jatuh tempo %s.',
                        $n, $kamar['nomor_kamar'], rupiah($jumlah), tgl($jt)
                    ));
                    $terbit++;
                }
                $n++;
            }
        }
        return $terbit;
    }

    /** BR-09: pengingat H-3 bila belum lunas. */
    public function kirimPengingat(): int
    {
        $now = date('Y-m-d H:i:s');
        $batas = date('Y-m-d H:i:s', strtotime('+3 days'));
        $n = 0;
        foreach ($this->tagihan->untukPengingat($now, $batas) as $t) {
            $this->tagihan->update((int)$t['id_tagihan'], ['pengingat_terkirim' => 1]);
            $this->notif->kirim((int)$t['id_penyewa'], 'pengingat', sprintf(
                'Pengingat: tagihan %s sebesar %s jatuh tempo %s.', $t['jenis'], rupiah($t['jumlah']), tgl($t['jatuh_tempo'])
            ));
            $n++;
        }
        return $n;
    }

    /** BR-09: tagihan yang lewat jatuh tempo berstatus Terlambat (denda belum diatur). */
    public function tandaiTerlambat(): int
    {
        $n = 0;
        foreach ($this->tagihan->untukTerlambat(date('Y-m-d H:i:s')) as $t) {
            $this->tagihan->update((int)$t['id_tagihan'], ['status' => 'Terlambat']);
            $this->notif->kirim((int)$t['id_penyewa'], 'pengingat', sprintf(
                'Tagihan sewa %s sebesar %s telah lewat jatuh tempo (%s). Mohon segera dibayar.', $t['jenis'], rupiah($t['jumlah']), tgl($t['jatuh_tempo'])
            ));
            $n++;
        }
        return $n;
    }

    public function riwayat(int $idPenyewa, int $page, int $per = 10): array
    {
        [$rows, $total] = $this->tagihan->paginateByPenyewa($idPenyewa, $page, $per);
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)max(1, ceil($total / $per))];
    }

    public function berikutnya(int $idPenyewa): ?array
    {
        return $this->tagihan->berikutnyaByPenyewa($idPenyewa);
    }

    public function detail(int $id): ?array
    {
        return $this->tagihan->detail($id);
    }

    public function byPemesanan(int $idPemesanan): array
    {
        return $this->tagihan->byPemesanan($idPemesanan);
    }
}
