<?php
declare(strict_types=1);

/** UC-12 Buat Laporan Pendapatan: data turunan dari pembayaran Disetujui (LaporanPendapatan bukan tabel). */
class LaporanService
{
    private PembayaranRepository $pembayaran;
    private KamarRepository $kamar;

    public function __construct()
    {
        $this->pembayaran = new PembayaranRepository();
        $this->kamar = new KamarRepository();
    }

    public static function periodeValid(?string $p): string
    {
        return ($p !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $p)) ? $p : date('Y-m');
    }

    public function rekap(string $periode): array
    {
        $awal = $periode . '-01 00:00:00';
        $akhir = date('Y-m-d H:i:s', strtotime($awal . ' +1 month'));
        $rows = $this->pembayaran->disetujuiBulan($awal, $akhir);
        $total = 0.0;
        $perJenis = [];
        foreach ($rows as $r) {
            $total += (float)$r['jumlah'];
            $perJenis[$r['jenis']] = ($perJenis[$r['jenis']] ?? 0) + (float)$r['jumlah'];
        }
        return [
            'periode' => $periode, 'rows' => $rows, 'total' => $total, 'perJenis' => $perJenis,
            'jumlahKamarTerisi' => $this->kamar->countByStatus()['Terisi'],
        ];
    }

    /** Pendapatan 6 bulan terakhir untuk grafik dashboard. */
    public function enamBulan(): array
    {
        $bulan = [];
        for ($i = 5; $i >= 0; $i--) {
            $bulan[] = date('Y-m', strtotime(date('Y-m-01') . " -$i months"));
        }
        $data = $this->pembayaran->totalPerBulan($bulan[0] . '-01 00:00:00');
        $out = [];
        foreach ($bulan as $b) {
            $out[] = ['bulan' => $b, 'total' => $data[$b] ?? 0.0];
        }
        return $out;
    }

    public function totalBulanIni(): float
    {
        return $this->rekap(date('Y-m'))['total'];
    }

    public function csv(string $periode): string
    {
        $r = $this->rekap($periode);
        $f = fopen('php://temp', 'r+');
        fputcsv($f, ['Tanggal Verifikasi', 'Kamar', 'Penyewa', 'Jenis', 'Periode', 'Jumlah']);
        foreach ($r['rows'] as $x) {
            fputcsv($f, [$x['tanggal_verifikasi'], $x['nomor_kamar'], $x['nama_penyewa'], $x['jenis'], $x['periode_ke'] ?? '', $x['jumlah']]);
        }
        fputcsv($f, ['', '', '', '', 'TOTAL', $r['total']]);
        rewind($f);
        $csv = stream_get_contents($f);
        fclose($f);
        return "\xEF\xBB\xBF" . $csv;
    }
}
