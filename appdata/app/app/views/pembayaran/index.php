<div class="page-head"><div><h1>Pembayaran</h1><p class="muted" style="margin:0">Status, jatuh tempo, dan riwayat tagihan Anda.</p></div></div>

<?php include BASE_PATH . '/app/views/_menunggak.php'; ?>

<div class="card">
  <h2>Tagihan Berikutnya</h2>
  <?php if ($berikutnya === null): ?>
    <div class="muted">Tidak ada tagihan yang harus dibayar saat ini.</div>
  <?php else: ?>
    <div class="grid grid-3">
      <div>
        <div class="small muted"><?= e($berikutnya['jenis']) ?> &middot; Kamar <?= e($berikutnya['nomor_kamar']) ?></div>
        <div style="font-size:22px;font-weight:700;color:var(--primer)"><?= e(rupiah(total_tagihan($berikutnya))) ?></div>
        <?php if ((float)$berikutnya['denda'] > 0): ?><div class="small" style="color:var(--danger)">pokok <?= e(rupiah($berikutnya['jumlah'])) ?> + denda <?= e(rupiah($berikutnya['denda'])) ?></div><?php endif; ?>
      </div>
      <div><div class="small muted">Jatuh tempo</div><div><strong><?= e(tgl($berikutnya['jatuh_tempo'], $berikutnya['jenis'] === 'DP')) ?></strong></div></div>
      <div><div class="small muted">Status</div><?= badge_tagihan($berikutnya) ?><?= janji_info($berikutnya) ?></div>
    </div>
    <div class="btn-row" style="margin-top:14px">
      <?php if ($berikutnya['bisa_unggah']): ?><a class="btn" href="/pembayaran/upload/<?= (int)$berikutnya['id_tagihan'] ?>">Upload Bukti Pembayaran</a><?php endif; ?>
      <?php if (bisa_janji($berikutnya)): ?><a class="btn btn-outline" href="/pembayaran/janji/<?= (int)$berikutnya['id_tagihan'] ?>">Ajukan Janji Bayar</a><?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Riwayat Tagihan</h2>
  <?php if ($riwayat['rows'] === []): ?>
    <div class="empty">Belum ada riwayat pembayaran.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Kamar</th><th>Jenis</th><th class="num">Jumlah</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($riwayat['rows'] as $t): ?>
        <tr>
          <td data-label="Kamar"><?= e($t['nomor_kamar']) ?></td>
          <td data-label="Jenis"><?= e($t['jenis']) ?><?= $t['periode_ke'] ? ' #' . (int)$t['periode_ke'] : '' ?></td>
          <td data-label="Jumlah" class="num"><?= tampil_total_tagihan($t) ?></td>
          <td data-label="Jatuh tempo"><?= e(tgl($t['jatuh_tempo'])) ?></td>
          <td data-label="Status"><?= badge_tagihan($t) ?><?= janji_info($t) ?>
            <?php if ($t['status_bayar'] === 'Ditolak' && $t['status'] !== 'Lunas'): ?><div class="small" style="color:var(--bahaya)">Ditolak: <?= e($t['catatan_bayar']) ?></div><?php endif; ?>
          </td>
          <td>
            <div class="row-actions">
              <?php if ($t['bisa_unggah']): ?><a class="btn btn-sm" href="/pembayaran/upload/<?= (int)$t['id_tagihan'] ?>">Upload Bukti</a><?php endif; ?>
              <?php if (bisa_janji($t)): ?><a class="btn btn-outline btn-sm" href="/pembayaran/janji/<?= (int)$t['id_tagihan'] ?>">Janji Bayar</a><?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($page, $riwayat['pages']) ?>
  <?php endif; ?>
</div>
