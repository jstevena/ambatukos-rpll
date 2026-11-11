<p class="small"><a href="/pembayaran">&larr; Pembayaran</a></p>
<div class="page-head"><h1>Upload Bukti Pembayaran</h1></div>
<div class="card form-wide">
  <dl class="kv">
    <dt>Kamar</dt><dd><?= e($t['nomor_kamar']) ?></dd>
    <dt>Jenis tagihan</dt><dd><?= e($t['jenis']) ?><?= $t['periode_ke'] ? ' #' . (int)$t['periode_ke'] : '' ?></dd>
    <dt>Jumlah transfer</dt><dd><strong style="font-size:18px;color:var(--primer)"><?= rupiah($t['jumlah']) ?></strong></dd>
    <dt>Jatuh tempo</dt><dd><?= e(tgl($t['jatuh_tempo'], $t['jenis'] === 'DP')) ?></dd>
    <dt>Status</dt><dd><?= badge($t['status']) ?></dd>
  </dl>
  <?php if ($bank !== ''): ?><div class="dp-box" style="margin-top:14px"><div class="small muted">Transfer ke</div><div><strong><?= e($bank) ?></strong></div></div><?php endif; ?>

  <?php if ($bisa): ?>
    <form method="post" enctype="multipart/form-data" action="/pembayaran/upload/<?= (int)$t['id_tagihan'] ?>" style="margin-top:16px">
      <?= csrf_field() ?>
      <div class="field">
        <label for="bukti">Bukti transfer</label>
        <input type="file" id="bukti" name="bukti" accept="image/jpeg,image/png,application/pdf" required>
        <div class="hint">JPG, PNG, atau PDF, maksimal 2 MB.</div>
      </div>
      <div class="btn-row"><button class="btn" type="submit">Kirim Bukti</button><a class="btn btn-outline" href="/pembayaran">Batal</a></div>
    </form>
  <?php else: ?>
    <div class="alert alert-warn" style="margin-top:16px">Tagihan ini tidak dapat diunggah buktinya (status: <?= e($t['status']) ?>).</div>
  <?php endif; ?>
</div>
