<div class="page-head">
  <div><h1>Kelola Kamar</h1><p class="muted" style="margin:0"><?= (int)$d['total'] ?> kamar</p></div>
  <a class="btn" href="/admin/kamar/baru">+ Tambah Kamar</a>
</div>
<div class="card">
  <?php if ($d['rows'] === []): ?>
    <div class="empty">Belum ada data kamar.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Kamar</th><th>Luas</th><th class="num">Biaya / bulan</th><th>Jenis</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($d['rows'] as $k): ?>
        <tr>
          <td data-label="Kamar"><strong><?= e($k['nomor_kamar']) ?></strong></td>
          <td data-label="Luas"><?= e(rtrim(rtrim((string)$k['luas'], '0'), '.')) ?> m&sup2;</td>
          <td data-label="Biaya / bulan" class="num"><?= rupiah($k['biaya_per_bulan']) ?></td>
          <td data-label="Jenis"><?= e(ucfirst($k['jenis_sewa'])) ?></td>
          <td data-label="Status"><?= badge($k['status']) ?></td>
          <td data-label="Aksi">
            <div class="row-actions">
              <a class="btn btn-outline btn-sm" href="/admin/kamar/<?= (int)$k['id_kamar'] ?>/ubah">Ubah</a>
              <?php if (isset($sewaAktif[(int)$k['id_kamar']])): ?>
                <form method="post" action="/admin/pemesanan/<?= (int)$sewaAktif[(int)$k['id_kamar']] ?>/akhiri" data-confirm="Akhiri sewa kamar <?= e($k['nomor_kamar']) ?>? Kamar akan kembali Tersedia.">
                  <?= csrf_field() ?><button class="btn btn-outline btn-sm" type="submit">Akhiri Sewa</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/admin/kamar/<?= (int)$k['id_kamar'] ?>/hapus" data-confirm="Hapus kamar <?= e($k['nomor_kamar']) ?>? Tindakan ini tidak dapat dibatalkan.">
                <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Hapus</button>
              </form>
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
