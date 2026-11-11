<p class="small"><a href="/pemesanan">&larr; Pemesanan saya</a></p>
<div class="page-head">
  <div><h1>Pemesanan #<?= (int)$p['id_pemesanan'] ?> &middot; Kamar <?= e($p['nomor_kamar']) ?></h1></div>
  <?= badge($p['status']) ?>
</div>

<div class="card">
  <h2>Ringkasan</h2>
  <dl class="kv">
    <dt>Tanggal pesan</dt><dd><?= e(tgl($p['tanggal_pesan'], true)) ?></dd>
    <dt>Tanggal kunjungan</dt><dd><?= e(tgl($p['tanggal_kunjungan'])) ?></dd>
    <dt>Tanggal masuk</dt><dd><?= e(tgl($p['tanggal_masuk'])) ?></dd>
    <dt>Jenis sewa</dt><dd><?= e(ucfirst($p['jenis_sewa'])) ?><?= $p['durasi_bulan'] ? ', ' . (int)$p['durasi_bulan'] . ' bulan' : '' ?></dd>
    <?php if ($p['tanggal_mulai_sewa']): ?><dt>Mulai sewa</dt><dd><?= e(tgl($p['tanggal_mulai_sewa'])) ?></dd><?php endif; ?>
    <?php if ($p['status'] === 'Menunggu DP'): ?><dt>Batas bayar DP</dt><dd><strong style="color:var(--bahaya)"><?= e(tgl($p['batas_bayar_dp'], true)) ?></strong></dd><?php endif; ?>
    <?php if ($p['alasan_batal']): ?><dt>Alasan pembatalan</dt><dd><?= e($p['alasan_batal']) ?></dd><?php endif; ?>
  </dl>
</div>

<?php if ($p['status'] === 'Menunggu Pelunasan'): ?>
  <div class="alert alert-info"><strong>Penting:</strong> saat kunjungan, jika kamar sudah sesuai dan Anda setuju (OK), serahkan <strong>fotokopi KTP</strong> kepada pengelola kos sebelum melunasi pembayaran.</div>
<?php endif; ?>

<div class="card">
  <h2>Tagihan</h2>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Jenis</th><th class="num">Jumlah</th><th>Jatuh tempo</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($tagihan as $t): ?>
        <tr>
          <td data-label="Jenis"><?= e($t['jenis']) ?><?= $t['periode_ke'] ? ' #' . (int)$t['periode_ke'] : '' ?></td>
          <td data-label="Jumlah" class="num"><?= rupiah($t['jumlah']) ?></td>
          <td data-label="Jatuh tempo"><?= e(tgl($t['jatuh_tempo'], $t['jenis'] === 'DP')) ?></td>
          <td data-label="Status"><?= badge($t['status']) ?>
            <?php if ($t['status_bayar'] === 'Ditolak' && $t['status'] === 'Belum Dibayar'): ?><div class="small" style="color:var(--bahaya)">Ditolak: <?= e($t['catatan_bayar']) ?></div><?php endif; ?>
          </td>
          <td><?php if ($t['bisa_unggah']): ?><a class="btn btn-sm" href="/pembayaran/upload/<?= (int)$t['id_tagihan'] ?>">Upload Bukti</a><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (in_array($p['status'], ['Menunggu DP', 'Menunggu Pelunasan'], true)): ?>
  <div class="card form-wide">
    <h2>Batalkan Pemesanan</h2>
    <p class="muted">
      <?php if ($p['status'] === 'Menunggu Pelunasan'): ?>
        Setelah melihat kamar dan merasa tidak cocok, Anda dapat membatalkan sebelum jatuh tempo pelunasan. DP dikembalikan penuh oleh admin.
      <?php else: ?>
        Pemesanan dapat dibatalkan selama menunggu DP. Jika Anda sudah mengunggah bukti DP, nominalnya akan dikembalikan oleh admin.
      <?php endif; ?>
    </p>
    <form method="post" action="/pemesanan/<?= (int)$p['id_pemesanan'] ?>/batal" data-confirm="Yakin ingin membatalkan pemesanan ini?">
      <?= csrf_field() ?>
      <div class="field">
        <label for="alasan">Alasan pembatalan</label>
        <input type="text" id="alasan" name="alasan" maxlength="255" required placeholder="mis. Kamar tidak sesuai setelah dilihat">
      </div>
      <button class="btn btn-danger" type="submit">Batalkan Pemesanan</button>
    </form>
  </div>
<?php endif; ?>
