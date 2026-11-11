<?php
$role = Auth::role();
$fas = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', (string)$k['fasilitas']) ?: [])));
$dp = round((float)$k['biaya_sekarang'] * $dpPersen / 100);
?>
<p class="small"><a href="/kamar">&larr; Daftar kamar</a></p>
<div class="page-head">
  <div><h1>Kamar <?= e($k['nomor_kamar']) ?></h1></div>
  <?= badge($k['status']) ?>
</div>

<div class="gallery">
  <?php if ($k['fotos'] === []): ?>
    <div class="ph"><?= e($k['nomor_kamar']) ?></div>
  <?php else: foreach ($k['fotos'] as $f): ?>
    <img src="<?= e(foto_url($f['path_file'])) ?>" alt="Foto kamar <?= e($k['nomor_kamar']) ?>" loading="lazy">
  <?php endforeach; endif; ?>
</div>

<div class="grid grid-2">
  <div class="card">
    <h2>Informasi Kamar</h2>
    <dl class="kv">
      <dt>Luas</dt><dd><?= e(rtrim(rtrim((string)$k['luas'], '0'), '.')) ?> m&sup2;</dd>
      <dt>Jenis sewa</dt><dd><?= e(ucfirst($k['jenis_sewa'])) ?></dd>
      <dt>Biaya per bulan</dt><dd><strong><?= rupiah($k['biaya_sekarang']) ?></strong></dd>
      <dt>Biaya per tahun</dt><dd><?= $k['biaya_per_tahun'] !== null ? rupiah($k['biaya_per_tahun']) : '-' ?></dd>
      <dt>Status</dt><dd><?= badge($k['status']) ?></dd>
    </dl>
    <?php if ($k['kenaikan']): ?>
      <div class="alert alert-info" style="margin:12px 0 0">Biaya sewa akan naik menjadi <?= rupiah($k['kenaikan']['biaya_baru']) ?>/bulan mulai <?= e(tgl($k['kenaikan']['tanggal_berlaku'])) ?>.</div>
    <?php endif; ?>
    <h3 style="margin-top:16px">Fasilitas</h3>
    <div><?php foreach ($fas as $f): ?><span class="chip"><?= e($f) ?></span><?php endforeach; ?><?php if ($fas === []): ?><span class="muted">-</span><?php endif; ?></div>
  </div>

  <div class="card">
    <h2>Pemesanan</h2>
    <div class="dp-box">
      <div class="small muted">DP <?= e(rtrim(rtrim(number_format($dpPersen, 2, '.', ''), '0'), '.')) ?>% dari biaya periode pertama</div>
      <div class="big"><?= rupiah($dp) ?></div>
      <div class="small">Bayar DP dalam 24 jam untuk menahan kamar. Lunasi setelah melihat kamar secara langsung.</div>
    </div>
    <div class="btn-row" style="margin-top:14px">
      <?php if (in_array($role, ['admin', 'pemilik'], true)): ?>
        <span class="muted small">Pemesanan dilakukan oleh calon penyewa.</span>
      <?php elseif ($k['status'] === 'Tersedia'): ?>
        <a class="btn" href="/pesan/<?= (int)$k['id_kamar'] ?>">Pesan Kamar</a>
        <?php if (!$role): ?><span class="muted small">Anda akan diminta masuk atau mendaftar terlebih dahulu.</span><?php endif; ?>
      <?php else: ?>
        <span class="btn disabled" aria-disabled="true">Kamar tidak tersedia</span>
      <?php endif; ?>
    </div>
  </div>
</div>
