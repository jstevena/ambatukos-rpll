<p class="small"><a href="/pembayaran">&larr; Pembayaran</a></p>
<div class="page-head"><h1>Upload Bukti Pembayaran</h1></div>
<div class="card form-wide">
  <dl class="kv">
    <dt>Kamar</dt><dd><?= e($t['nomor_kamar']) ?></dd>
    <dt>Jenis tagihan</dt><dd><?= e($t['jenis']) ?><?= $t['periode_ke'] ? ' #' . (int)$t['periode_ke'] : '' ?></dd>
    <dt>Tagihan pokok</dt><dd><?= rupiah($t['jumlah']) ?></dd>
    <?php if ((float)$t['denda'] > 0): ?><dt>Denda</dt><dd style="color:var(--danger)"><?= rupiah($t['denda']) ?></dd><?php endif; ?>
    <dt>Total transfer</dt><dd><strong style="font-size:18px;color:var(--primer)"><?= rupiah(total_tagihan($t)) ?></strong></dd>
    <dt>Jatuh tempo</dt><dd><?= e(tgl($t['jatuh_tempo'], $t['jenis'] === 'DP')) ?></dd>
    <dt>Status</dt><dd><?= badge_tagihan($t) ?><?= janji_info($t) ?></dd>
  </dl>
  <?php if ($bank !== ''): ?><div class="dp-box" style="margin-top:14px"><div class="small muted">Transfer ke</div><div><strong><?= e($bank) ?></strong></div></div><?php endif; ?>

  <?php if ($bisa): ?>
    <form method="post" enctype="multipart/form-data" action="/pembayaran/upload/<?= (int)$t['id_tagihan'] ?>" style="margin-top:16px">
      <?= csrf_field() ?>
      <div class="field">
        <label for="bukti">Bukti transfer</label>
        <input type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,application/pdf" required>
        <div class="hint">JPG, PNG, atau PDF, maksimal 2 MB. Pastikan nominal transfer sama dengan total di atas.</div>
      </div>
      <div class="btn-row"><button class="btn" type="submit">Kirim Bukti</button><a class="btn btn-outline" href="/pembayaran">Batal</a></div>
    </form>
  <?php else: ?>
    <div class="alert alert-warn" style="margin-top:16px">Tagihan ini tidak dapat diunggah buktinya (status: <?= e($t['status']) ?>).</div>
  <?php endif; ?>
  <?php if (bisa_janji($t) && $t['status_pemesanan'] === 'Aktif'): ?>
    <p class="small" style="margin-top:14px">Belum bisa membayar sekarang? <a href="/pembayaran/janji/<?= (int)$t['id_tagihan'] ?>">Ajukan janji bayar</a>.</p>
  <?php endif; ?>
</div>
