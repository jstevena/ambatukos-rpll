<?php $pemilik = Auth::role() === 'pemilik'; ?>
<div class="page-head">
  <div><h1><?= $pemilik ? 'Kelola Kamar' : 'Data Kamar' ?></h1><p class="muted" style="margin:0"><?= (int)$d['total'] ?> kamar</p></div>
  <?php if ($pemilik): ?><a class="btn" href="/admin/kamar/baru">+ Tambah Kamar</a><?php endif; ?>
</div>
<?php if (!$pemilik): ?><div class="alert alert-info">Detail dan harga kamar hanya dapat diubah oleh pemilik kos.</div><?php endif; ?>
<div class="card">
  <?php if ($d['rows'] === []): ?>
    <div class="empty">Belum ada data kamar.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Kamar</th><th>Luas</th><th class="num">Biaya / bulan</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($d['rows'] as $k): ?>
        <tr>
          <td data-label="Kamar"><strong><?= e($k['nomor_kamar']) ?></strong></td>
          <td data-label="Luas"><?= e(rtrim(rtrim((string)$k['luas'], '0'), '.')) ?> m&sup2;</td>
          <td data-label="Biaya / bulan" class="num"><?= rupiah($k['biaya_per_bulan']) ?></td>
          <td data-label="Status"><?= badge($k['status']) ?></td>
          <td data-label="Aksi">
            <div class="row-actions">
              <?php if ($pemilik): ?><a class="btn btn-outline btn-sm" href="/admin/kamar/<?= (int)$k['id_kamar'] ?>/ubah">Ubah</a><?php endif; ?>
              <?php if (isset($sewaAktif[(int)$k['id_kamar']])): ?>
                <form method="post" action="/admin/pemesanan/<?= (int)$sewaAktif[(int)$k['id_kamar']] ?>/akhiri" data-confirm="Akhiri sewa kamar <?= e($k['nomor_kamar']) ?>? Kamar akan kembali Tersedia.">
                  <?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Akhiri Sewa</button>
                </form>
              <?php endif; ?>
              <?php if ($pemilik): ?>
              <form method="post" action="/admin/kamar/<?= (int)$k['id_kamar'] ?>/hapus" data-confirm="Hapus kamar <?= e($k['nomor_kamar']) ?>? Tindakan ini tidak dapat dibatalkan.">
                <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($page, $d['pages']) ?>
  <?php endif; ?>
</div>
