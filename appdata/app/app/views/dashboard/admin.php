<div class="page-head"><div><h1>Dashboard Admin</h1><p class="muted" style="margin:0">Ringkasan operasional kos.</p></div></div>

<div class="grid grid-4" style="margin-bottom:16px">
  <div class="stat"><div class="n"><?= (int)$totalKamar ?></div><div class="l">Total Kamar</div></div>
  <div class="stat"><div class="n"><?= (int)$status['Terisi'] ?></div><div class="l">Kamar Terisi</div></div>
  <a class="stat info" href="/admin/verifikasi" style="text-decoration:none;color:inherit"><div class="n"><?= (int)$jumlahMenunggu ?></div><div class="l">Menunggu Verifikasi</div></a>
  <a class="stat danger" href="/admin/belum-bayar" style="text-decoration:none;color:inherit"><div class="n"><?= (int)$belumBayar ?></div><div class="l">Penyewa Belum Bayar</div></a>
</div>

<div class="grid grid-2">
  <div class="card"><h2>Grafik Pendapatan (6 bulan)</h2><?php include __DIR__ . '/_chart.php'; ?></div>
  <div class="card"><h2>Status Kamar</h2><?php include __DIR__ . '/_status.php'; ?></div>
</div>

<div class="card">
  <h2>Pembayaran Menunggu Verifikasi</h2>
  <?php if ($menunggu === []): ?>
    <div class="empty">Tidak ada pembayaran yang menunggu verifikasi.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Dibayar</th><th>Penyewa</th><th>Kamar</th><th>Jenis</th><th class="num">Jumlah</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($menunggu as $x): ?>
        <tr>
          <td data-label="Dibayar"><?= e(tgl($x['tanggal_bayar'], true)) ?></td>
          <td data-label="Penyewa"><?= e($x['nama_penyewa']) ?></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Jenis"><?= e($x['jenis']) ?></td>
          <td data-label="Jumlah" class="num"><?= rupiah($x['jumlah']) ?></td>
          <td><a class="btn btn-sm" href="/admin/verifikasi/<?= (int)$x['id_pembayaran'] ?>">Tinjau</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
