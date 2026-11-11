<div class="page-head"><div><h1>Dashboard Pemilik Kos</h1><p class="muted" style="margin:0">Pantau usaha kos Anda.</p></div></div>

<div class="grid grid-4" style="margin-bottom:16px">
  <div class="stat"><div class="n"><?= (int)$totalKamar ?></div><div class="l">Total Kamar</div></div>
  <div class="stat"><div class="n"><?= (int)$status['Terisi'] ?></div><div class="l">Kamar Terisi</div></div>
  <div class="stat info"><div class="n" style="font-size:20px"><?= rupiah($bulanIni) ?></div><div class="l">Pendapatan bulan ini</div></div>
  <div class="stat danger"><div class="n"><?= (int)$belumBayar ?></div><div class="l">Penyewa belum bayar</div></div>
</div>

<div class="grid grid-2">
  <div class="card"><h2>Grafik Pendapatan (6 bulan)</h2><?php include __DIR__ . '/_chart.php'; ?></div>
  <div class="card"><h2>Status Kamar</h2><?php include __DIR__ . '/_status.php'; ?></div>
</div>

<div class="card">
  <h2>Tindakan</h2>
  <div class="btn-row">
    <a class="btn" href="/pemilik/kenaikan-sewa">Tetapkan Kenaikan Sewa</a>
    <a class="btn btn-outline" href="/laporan">Lihat Laporan Pendapatan</a>
  </div>
  <?php if ($ditetapkan > 0): ?><p class="muted small" style="margin-top:10px"><?= (int)$ditetapkan ?> penetapan kenaikan sewa menunggu diumumkan admin.</p><?php endif; ?>
</div>
