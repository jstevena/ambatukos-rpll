<?php
declare(strict_types=1);

class LaporanController extends Controller
{
    /** UI-13 Laporan Pendapatan. */
    public function index(): void
    {
        $periode = LaporanService::periodeValid($_GET['periode'] ?? null);
        $this->view('laporan/index', ['title' => 'Laporan Pendapatan', 'r' => (new LaporanService())->rekap($periode), 'periode' => $periode]);
    }

    public function unduh(): void
    {
        $periode = LaporanService::periodeValid($_GET['periode'] ?? null);
        $csv = (new LaporanService())->csv($periode);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="laporan-pendapatan-' . $periode . '.csv"');
        echo $csv;
        exit;
    }
}
