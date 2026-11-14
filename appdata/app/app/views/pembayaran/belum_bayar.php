<div class="page-head">
  <div><h1>Penyewa Belum Bayar</h1><p class="muted" style="margin:0">Tagihan yang sudah terbit dan belum dibayar, lengkap dengan fase keterlambatan dan denda.</p></div>
  <a class="btn btn-outline" href="/admin/denda">Pengaturan Denda</a>
</div>

<?php if ($janji !== []): ?>
<div class="card">
  <h2>Janji Bayar Menunggu Persetujuan (<?= count($janji) ?>)</h2>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Penyewa</th><th>Kamar</th><th class="num">Total tagihan</th><th>Janji bayar</th><th>Alasan</th><th>Keputusan</th></tr></thead>
      <tbody>
      <?php foreach ($janji as $j): ?>
        <tr>
          <td data-label="Penyewa"><strong><?= e($j['nama_penyewa']) ?></strong><div class="small muted"><?= e(tampil_hp($j['no_hp'])) ?></div></td>
          <td data-label="Kamar"><?= e($j['nomor_kamar']) ?></td>
          <td data-label="Total tagihan" class="num"><?= rupiah((float)$j['jumlah_tagihan'] + (float)$j['denda']) ?></td>
          <td data-label="Janji bayar"><?= e(tgl($j['tanggal_janji'])) ?></td>
          <td data-label="Alasan"><?= e($j['alasan']) ?></td>
          <td>
            <div class="row-actions">
              <form method="post" action="/admin/janji/<?= (int)$j['id_janji'] ?>/setujui" data-confirm="Setujui janji bayar? Denda dijeda sampai <?= e(tgl($j['tanggal_janji'])) ?>.">
                <?= csrf_field() ?><button class="btn btn-sm" type="submit">Setujui</button>
              </form>
              <form method="post" action="/admin/janji/<?= (int)$j['id_janji'] ?>/tolak">
                <?= csrf_field() ?>
                <input type="text" name="catatan" maxlength="255" placeholder="Alasan penolakan" required style="width:170px;display:inline-block">
                <button class="btn btn-danger btn-sm" type="submit">Tolak</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <?php if ($daftar === []): ?>
    <div class="empty">Semua penyewa sudah membayar tagihannya.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Penyewa</th><th>Kamar</th><th>Tagihan</th><th class="num">Pokok</th><th class="num">Denda</th><th class="num">Total</th><th>Jatuh tempo</th><th>Telat</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $x):
          $f = fase_tagihan($x);
          $sewa = in_array($x['jenis'], ['Sewa Bulanan', 'Sewa Tahunan'], true);
          $bisaPutus = $sewa && (int)$x['hari_telat'] > $batasPutus;
      ?>
        <tr>
          <td data-label="Penyewa"><strong><?= e($x['nama_penyewa']) ?></strong><div class="small muted"><?= e(tampil_hp($x['no_hp'])) ?></div></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Tagihan"><?= e($x['jenis']) ?><?= $x['periode_ke'] ? ' #' . (int)$x['periode_ke'] : '' ?></td>
          <td data-label="Pokok" class="num"><?= rupiah($x['jumlah']) ?></td>
          <td data-label="Denda" class="num"><?= (float)$x['denda'] > 0 ? '<span style="color:var(--danger)">' . e(rupiah($x['denda'])) . '</span>' : '-' ?></td>
          <td data-label="Total" class="num"><strong><?= rupiah(total_tagihan($x)) ?></strong></td>
          <td data-label="Jatuh tempo"><?= e(tgl($x['jatuh_tempo'])) ?></td>
          <td data-label="Telat"><?= (int)$x['hari_telat'] > 0 ? '<strong style="color:var(--bahaya)">' . (int)$x['hari_telat'] . ' hari</strong>' : '-' ?></td>
          <td data-label="Status">
            <?= badge($f['label'], $f['kelas']) ?>
            <?php if ($f['label'] === 'Menunggak'): ?><div style="margin-top:4px"><?= badge('Prioritas Tagih Langsung', 'danger') ?></div><?php endif; ?>
            <?= janji_info($x) ?>
          </td>
          <td data-label="Aksi">
            <?php if ($bisaPutus && $x['id_pemesanan']): ?>
              <form method="post" action="/admin/pemesanan/<?= (int)$x['id_pemesanan'] ?>/putus" data-confirm="PUTUS SEWA SEPIHAK untuk <?= e($x['nama_penyewa']) ?>? Kamar <?= e($x['nomor_kamar']) ?> dikosongkan dan penyewa masuk daftar hitam. Tidak dapat dibatalkan.">
                <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Putus Sewa Sepihak</button>
              </form>
            <?php else: ?><span class="muted small">-</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <p class="muted small" style="margin:12px 0 0">Putus sewa sepihak tersedia setelah keterlambatan lebih dari <?= (int)$batasPutus ?> hari.</p>
  <?php endif; ?>
</div>
