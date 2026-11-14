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

    /** Pengingat sebelum jatuh tempo: H-3, H-1, dan hari H (tahap dicatat pada bitmask pengingat_terkirim). */
    public function kirimPengingat(): int
    {
        $hariIni = date('Y-m-d');
        $mulai = $hariIni . ' 00:00:00';
        $batas = date('Y-m-d', strtotime('+3 days')) . ' 23:59:59';
        $n = 0;
        foreach ($this->tagihan->untukPengingat($mulai, $batas) as $t) {
            $sisa = (int)round((strtotime(date('Y-m-d', strtotime($t['jatuh_tempo']))) - strtotime($hariIni)) / 86400);
            $flag = (int)$t['pengingat_terkirim'];
            if ($sisa <= 0 && !($flag & 4)) {
                $bit = 7;
                $kata = 'jatuh tempo HARI INI';
            } elseif ($sisa === 1 && !($flag & 2)) {
                $bit = 3;
                $kata = 'jatuh tempo BESOK (H-1)';
            } elseif ($sisa >= 2 && !($flag & 1)) {
                $bit = 1;
                $kata = "jatuh tempo $sisa hari lagi (H-$sisa)";
            } else {
                continue;
            }
            $ket = in_array($t['jenis'], ['Sewa Bulanan', 'Sewa Tahunan'], true) ? ' Hindari denda dengan membayar sebelum jatuh tempo.' : '';
            $this->tagihan->update((int)$t['id_tagihan'], ['pengingat_terkirim' => $flag | $bit]);
            $this->notif->kirim((int)$t['id_penyewa'], 'pengingat', sprintf('Pengingat: tagihan %s sebesar %s %s.%s', $t['jenis'], rupiah($t['jumlah']), $kata, $ket));
            $n++;
        }
        return $n;
    }

    /** Tagihan sewa yang lewat jatuh tempo berstatus Terlambat (pengingat harian dikirim terpisah). */
    public function tandaiTerlambat(): int
    {
        $n = 0;
        foreach ($this->tagihan->untukTerlambat(date('Y-m-d H:i:s')) as $t) {
            $this->tagihan->update((int)$t['id_tagihan'], ['status' => 'Terlambat']);
            $n++;
        }
        return $n;
    }

    /**
     * Denda dihitung per hari oleh penjadwal dan disimpan di tabel tagihan_denda (item terpisah dari harga pokok).
     * Denda mulai pada H+(masa tenggang + 1); hari yang tercakup janji bayar yang disetujui dilewati (dijeda).
     */
    public function hitungDenda(): int
    {
        $tipe = PengaturanService::get('denda_tipe');
        $nominal = (float)PengaturanService::get('denda_nominal');
        $maks = (float)PengaturanService::get('denda_maks');
        $grace = PengaturanService::int('grace_hari');
        $hariIni = date('Y-m-d');
        $diproses = 0;
        foreach ($this->tagihan->untukDenda(date('Y-m-d H:i:s')) as $t) {
            $id = (int)$t['id_tagihan'];
            $jatuhTempo = date('Y-m-d', strtotime($t['jatuh_tempo']));
            $d = $this->tagihan->dendaByTagihan($id);
            if ($d === null) {
                $awal = date('Y-m-d', strtotime("$jatuhTempo +$grace days"));
                $this->tagihan->buatDenda($id, $awal);
                $d = ['jumlah' => 0, 'hari' => 0, 'terakhir_dihitung' => $awal];
            }
            $jumlah = (float)$d['jumlah'];
            $hari = (int)$d['hari'];
            $sampaiJanji = $this->tagihan->janjiDisetujuiSampai($id);
            $tgl = date('Y-m-d', strtotime($d['terakhir_dihitung'] . ' +1 day'));
            for ($i = 0; $tgl <= $hariIni && $i < 400; $i++, $tgl = date('Y-m-d', strtotime("$tgl +1 day"))) {
                if ($sampaiJanji !== null && $tgl <= $sampaiJanji) {
                    continue; // dijeda oleh janji bayar
                }
                if ($tipe === 'flat') {
                    if ($jumlah <= 0) {
                        $jumlah = $nominal;
                        $hari = 1;
                    }
                } else {
                    $jumlah += $nominal;
                    $hari++;
                    if ($maks > 0 && $jumlah > $maks) {
                        $jumlah = $maks;
                    }
                }
            }
            $terakhir = max($d['terakhir_dihitung'], $hariIni);
            $this->tagihan->simpanDenda($id, $jumlah, $hari, $terakhir);
            $diproses++;
        }
        return $diproses;
    }

    /** Pengingat harian untuk tagihan yang sudah lewat jatuh tempo (masa tenggang, denda berjalan, menunggak). */
    public function kirimPengingatTerlambat(): int
    {
        $hariIni = date('Y-m-d');
        $n = 0;
        foreach ($this->tagihan->untukPengingatTerlambat($hariIni, date('Y-m-d H:i:s')) as $t) {
            $fase = fase_tagihan($t)['label'];
            $hari = hari_telat($t['jatuh_tempo']);
            $denda = (float)$t['denda'];
            $total = (float)$t['jumlah'] + $denda;
            if ($fase === 'Menunggak') {
                $pesan = sprintf('PERINGATAN KERAS: tagihan kamar %s menunggak %d hari. Total yang harus dibayar %s (termasuk denda %s). Fitur keluhan dikunci sampai tagihan lunas.', $t['nomor_kamar'], $hari, rupiah($total), rupiah($denda));
            } elseif ($fase === 'Terlambat (Denda Berjalan)') {
                $pesan = sprintf('Tagihan kamar %s terlambat %d hari. Denda berjalan %s, total %s. Segera lakukan pembayaran.', $t['nomor_kamar'], $hari, rupiah($denda), rupiah($total));
            } else {
                $pesan = sprintf('Tagihan kamar %s terlambat %d hari (masa tenggang, denda belum berlaku). Segera bayar %s.', $t['nomor_kamar'], $hari, rupiah($total));
            }
            $this->tagihan->update((int)$t['id_tagihan'], ['pengingat_terakhir' => $hariIni]);
            $this->notif->kirim((int)$t['id_penyewa'], 'pengingat', $pesan);
            $n++;
        }
        return $n;
    }

    /** Tagihan yang menunggak (>= H+8 bawaan) milik penyewa; null bila tidak ada. */
    public function menunggak(int $idPenyewa): ?array
    {
        return $this->tagihan->menunggakByPenyewa($idPenyewa, date('Y-m-d'), PengaturanService::int('menunggak_hari'));
    }

    public function jumlahPrioritasTagih(): int
    {
        return $this->tagihan->jumlahPrioritas(date('Y-m-d'), PengaturanService::int('menunggak_hari'));
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
