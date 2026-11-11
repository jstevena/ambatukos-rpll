<?php $peran = Auth::role(); ?>
<div class="page-head">
  <div><h1>Notifikasi</h1></div>
  <?php if ($d['rows'] !== []): ?>
    <form method="post" action="/notifikasi/baca-semua"><?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Tandai semua dibaca</button></form>
  <?php endif; ?>
</div>
<div class="card">
  <?php if ($d['rows'] === []): ?>
    <div class="empty">Belum ada notifikasi.</div>
  <?php else: foreach ($d['rows'] as $n): ?>
    <div class="notif <?= $n['status_baca'] ? '' : 'unread' ?>">
      <div>
        <a href="<?= e(link_notifikasi($n['jenis'], $peran)) ?>" style="color:inherit;text-decoration:none"><?= e($n['pesan']) ?></a>
        <div class="meta"><?= e(tgl($n['tanggal_kirim'], true)) ?></div>
      </div>
      <?php if (!$n['status_baca']): ?>
        <form method="post" action="/notifikasi/baca/<?= (int)$n['id_notifikasi'] ?>"><?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Dibaca</button></form>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
  <?= pagination($page, $d['pages']) ?>
</div>
