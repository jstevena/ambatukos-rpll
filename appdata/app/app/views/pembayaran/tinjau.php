<?php $pdf = str_ends_with((string)$pb['bukti_transfer'], '.pdf'); $buktiUrl = '/berkas/bukti/' . (int)$pb['id_pembayaran']; ?>
<p class="small"><a href="/admin/verifikasi">&larr; Verifikasi pembayaran</a></p>
<div class="page-head"><h1>Tinjau Pembayaran #<?= (int)$pb['id_pembayaran'] ?></h1><?= badge($pb['status']) ?></div>

<div class="side-by-side">
  <div class="card">
    <h2>Bukti Transfer</h2>
    <?php if ($pdf): ?>
      <embed src="<?= e($buktiUrl) ?>" type="application/pdf" style="width:100%;height:460px">
      <p><a href="<?= e($buktiUrl) ?>" target="_blank" rel="noopener">Buka PDF di tab baru</a></p>
    <?php else: ?>
      <a href="<?= e($buktiUrl) ?>" target="_blank" rel="noopener"><img class="proof" src="<?= e($buktiUrl) ?>" alt="Bukti transfer"></a>
    <?php endif; ?>
  </div>

  <div>
    <div class="card">
      <h2>Rincian Tagihan</h2>
      <dl class="kv">
        <dt>Penyewa</dt><dd><?= e($pb['nama_penyewa']) ?> <small>(<?= e($pb['no_hp']) ?>)</small></dd>
        <dt>Kamar</dt><dd><?= e($pb['nomor_kamar']) ?></dd>
        <dt>Jenis</dt><dd><?= e($pb['jenis']) ?><?= $pb['periode_ke'] ? ' #' . (int)$pb['periode_ke'] : '' ?></dd>
        <dt>Tagihan pokok</dt><dd><?= rupiah($pb['jumlah_tagihan']) ?></dd>
        <?php if ((float)$pb['denda'] > 0): ?><dt>Denda</dt><dd style="color:var(--danger)"><?= rupiah($pb['denda']) ?></dd><?php endif; ?>
        <dt>Total dibayar</dt><dd><strong style="font-size:18px;color:var(--primer)"><?= rupiah($pb['jumlah']) ?></strong></dd>
        <dt>Jatuh tempo</dt><dd><?= e(tgl($pb['jatuh_tempo'])) ?></dd>
        <dt>Diunggah</dt><dd><?= e(tgl($pb['tanggal_bayar'], true)) ?></dd>
        <dt>Pemesanan</dt><dd><?= badge($pb['status_pemesanan']) ?></dd>
      </dl>
    </div>

    <?php if ($pb['status'] === 'Menunggu Verifikasi'): ?>
      <div class="card">
        <form method="post" action="/admin/verifikasi/<?= (int)$pb['id_pembayaran'] ?>/setujui" data-confirm="Setujui pembayaran ini?">
          <?= csrf_field() ?><button class="btn" type="submit" style="width:100%">Setujui Pembayaran</button>
        </form>
      </div>
      <div class="card">
        <h3>Bukti tidak sesuai?</h3>
        <form method="post" action="/admin/verifikasi/<?= (int)$pb['id_pembayaran'] ?>/tolak">
          <?= csrf_field() ?>
          <div class="field"><input type="text" name="catatan" maxlength="255" placeholder="Alasan penolakan" required></div>
          <button class="btn btn-danger" type="submit">Tolak</button>
        </form>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Pembayaran ini sudah diproses.<?= $pb['catatan_admin'] ? ' Catatan: ' . e($pb['catatan_admin']) : '' ?></div>
    <?php endif; ?>
  </div>
</div>
