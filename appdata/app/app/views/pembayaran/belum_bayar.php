<div class="page-head"><div><h1>Penyewa Belum Bayar</h1><p class="muted" style="margin:0">Tagihan yang sudah terbit dan belum dibayar.</p></div></div>
<div class="card">
  <?php if ($daftar === []): ?>
    <div class="empty">Semua penyewa sudah membayar tagihannya.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Penyewa</th><th>Kamar</th><th>Tagihan</th><th class="num">Jumlah</th><th>Jatuh tempo</th><th>Keterlambatan</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $x): ?>
        <tr>
          <td data-label="Penyewa"><strong><?= e($x['nama_penyewa']) ?></strong><div class="small muted"><?= e($x['no_hp']) ?></div></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Tagihan"><?= e($x['jenis']) ?><?= $x['periode_ke'] ? ' #' . (int)$x['periode_ke'] : '' ?></td>
          <td data-label="Jumlah" class="num"><?= rupiah($x['jumlah']) ?></td>
          <td data-label="Jatuh tempo"><?= e(tgl($x['jatuh_tempo'])) ?></td>
          <td data-label="Keterlambatan"><?= (int)$x['hari_telat'] > 0 ? '<strong style="color:var(--bahaya)">' . (int)$x['hari_telat'] . ' hari</strong>' : '-' ?></td>
          <td data-label="Status"><?= badge($x['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
