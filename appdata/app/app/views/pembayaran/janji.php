<p class="small"><a href="/pembayaran">&larr; Pembayaran</a></p>
<div class="page-head"><h1>Ajukan Janji Bayar</h1></div>
<div class="card form-wide">
  <dl class="kv">
    <dt>Kamar</dt><dd><?= e($t['nomor_kamar']) ?></dd>
    <dt>Tagihan</dt><dd><?= e($t['jenis']) ?><?= $t['periode_ke'] ? ' #' . (int)$t['periode_ke'] : '' ?></dd>
    <dt>Total saat ini</dt><dd><strong><?= rupiah(total_tagihan($t)) ?></strong><?= (float)$t['denda'] > 0 ? ' <small>(termasuk denda ' . e(rupiah($t['denda'])) . ')</small>' : '' ?></dd>
    <dt>Jatuh tempo</dt><dd><?= e(tgl($t['jatuh_tempo'])) ?></dd>
    <dt>Status</dt><dd><?= badge_tagihan($t) ?><?= janji_info($t) ?></dd>
  </dl>
  <div class="alert alert-info" style="margin-top:14px">
    Jika admin menyetujui, <strong>perhitungan denda dijeda</strong> sampai tanggal janji. Bila sampai tanggal itu tagihan belum lunas, denda <strong>langsung aktif kembali</strong>. Satu tagihan hanya dapat memiliki satu janji bayar.
  </div>

  <?php if ($bisa): ?>
  <form method="post" action="/pembayaran/janji/<?= (int)$t['id_tagihan'] ?>" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="tanggal_janji">Tanggal janji bayar</label>
      <input type="date" id="tanggal_janji" name="tanggal_janji" min="<?= e($min) ?>" max="<?= e($maks) ?>" value="<?= e(old('tanggal_janji')) ?>" required>
      <div class="hint">Paling lambat <?= (int)JanjiBayarService::MAKS_HARI ?> hari dari hari ini (sampai <?= e(tgl($maks)) ?>).</div>
    </div>
    <div class="field">
      <label for="alasan">Alasan</label>
      <textarea id="alasan" name="alasan" maxlength="255" required><?= e(old('alasan')) ?></textarea>
    </div>
    <div class="btn-row"><button class="btn" type="submit">Ajukan</button><a class="btn btn-outline" href="/pembayaran">Batal</a></div>
  </form>
  <?php else: ?>
    <div class="alert alert-warn">Janji bayar tidak dapat diajukan untuk tagihan ini (sudah dibayar, sudah memiliki janji, atau bukan tagihan sewa).</div>
  <?php endif; ?>
</div>
