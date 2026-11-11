<?php $user = Auth::user(); ?>
<div class="page-head"><div><h1>Halo, <?= e($user['nama']) ?></h1><p class="muted" style="margin:0">Ringkasan sewa dan pembayaran Anda.</p></div></div>

<div class="grid grid-2">
  <div class="card">
    <h2>Sewa Aktif</h2>
    <?php if ($aktif === []): ?>
      <div class="muted">Anda belum memiliki sewa aktif.</div>
      <div class="btn-row" style="margin-top:12px"><a class="btn" href="/kamar">Cari Kamar</a></div>
    <?php else: foreach ($aktif as $p): ?>
      <dl class="kv" style="margin-bottom:10px">
        <dt>Kamar</dt><dd><strong><?= e($p['nomor_kamar']) ?></strong> <?= badge($p['status']) ?></dd>
        <dt>Mulai sewa</dt><dd><?= e(tgl($p['tanggal_mulai_sewa'])) ?></dd>
        <dt>Jenis</dt><dd><?= e(ucfirst($p['jenis_sewa'])) ?><?= $p['durasi_bulan'] ? ', ' . (int)$p['durasi_bulan'] . ' bulan' : '' ?></dd>
      </dl>
    <?php endforeach; endif; ?>
    <?php foreach ($berjalan as $p): ?>
      <div class="alert alert-warn">Pemesanan kamar <?= e($p['nomor_kamar']) ?>: <?= e($p['status']) ?>. <a href="/pemesanan/<?= (int)$p['id_pemesanan'] ?>">Lihat</a></div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <h2>Tagihan Berikutnya</h2>
    <?php if ($berikutnya === null): ?>
      <div class="muted">Tidak ada tagihan yang harus dibayar.</div>
    <?php else: ?>
      <div class="small muted"><?= e($berikutnya['jenis']) ?> &middot; Kamar <?= e($berikutnya['nomor_kamar']) ?></div>
      <div style="font-size:24px;font-weight:700;color:var(--primer)"><?= rupiah($berikutnya['jumlah']) ?></div>
      <div>Jatuh tempo <strong><?= e(tgl($berikutnya['jatuh_tempo'])) ?></strong> <?= badge($berikutnya['status']) ?></div>
      <div class="btn-row" style="margin-top:12px"><a class="btn" href="/pembayaran">Lihat Pembayaran</a></div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <h2>Notifikasi Terbaru</h2>
  <?php if ($notifikasi === []): ?>
    <div class="muted">Belum ada notifikasi.</div>
  <?php else: foreach ($notifikasi as $n): ?>
    <div class="notif <?= $n['status_baca'] ? '' : 'unread' ?>"><div><?= e($n['pesan']) ?><div class="meta"><?= e(tgl($n['tanggal_kirim'], true)) ?></div></div></div>
  <?php endforeach; ?>
    <p style="margin:12px 0 0"><a href="/notifikasi">Semua notifikasi &rarr;</a></p>
  <?php endif; ?>
</div>
