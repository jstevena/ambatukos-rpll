<div class="page-head">
  <div><h1>Laporan Pendapatan</h1><p class="muted" style="margin:0">Dihitung dari pembayaran berstatus Disetujui.</p></div>
  <form method="get" action="/laporan" class="btn-row">
    <input type="month" name="periode" value="<?= e($periode) ?>" style="width:auto" onchange="this.form.submit()">
    <a class="btn" href="/laporan/unduh?periode=<?= e($periode) ?>">Unduh CSV</a>
  </form>
</div>

<div class="grid grid-3" style="margin-bottom:16px">
  <div class="stat"><div class="n"><?= rupiah($r['total']) ?></div><div class="l">Total pendapatan <?= e(BULAN_ID[(int)substr($periode, 5, 2)] . ' ' . substr($periode, 0, 4)) ?></div></div>
  <div class="stat info"><div class="n"><?= count($r['rows']) ?></div><div class="l">Jumlah transaksi</div></div>
  <div class="stat warn"><div class="n"><?= (int)$r['jumlahKamarTerisi'] ?></div><div class="l">Kamar terisi saat ini</div></div>
</div>

<?php if ($r['rows'] === []): ?>
  <div class="card empty">Tidak ada data pendapatan pada periode ini.</div>
<?php else: ?>
  <div class="card">
    <h2>Per Jenis Tagihan</h2>
    <div class="table-wrap"><table class="table-cards"><thead><tr><th>Jenis</th><th class="num">Jumlah</th></tr></thead><tbody>
      <?php foreach ($r['perJenis'] as $j => $v): ?><tr><td data-label="Jenis"><?= e($j) ?></td><td data-label="Jumlah" class="num"><?= rupiah($v) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="card">
    <h2>Rincian Transaksi</h2>
    <div class="table-wrap"><table class="table-cards">
      <thead><tr><th>Diverifikasi</th><th>Kamar</th><th>Penyewa</th><th>Jenis</th><th class="num">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($r['rows'] as $x): ?>
        <tr>
          <td data-label="Diverifikasi"><?= e(tgl($x['tanggal_verifikasi'], true)) ?></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Penyewa"><?= e($x['nama_penyewa']) ?></td>
          <td data-label="Jenis"><?= e($x['jenis']) ?><?= $x['periode_ke'] ? ' #' . (int)$x['periode_ke'] : '' ?></td>
          <td data-label="Jumlah" class="num"><?= rupiah($x['jumlah']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>
<?php endif; ?>
