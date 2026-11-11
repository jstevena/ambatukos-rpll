<div class="page-head">
  <div><h1>Pemesanan Saya</h1></div>
  <a class="btn btn-outline" href="/kamar">Cari kamar</a>
</div>
<div class="card">
  <?php if ($daftar === []): ?>
    <div class="empty">Anda belum memiliki pemesanan. <a href="/kamar">Lihat daftar kamar</a>.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>#</th><th>Kamar</th><th>Dipesan</th><th>Kunjungan</th><th>Masuk</th><th>Jenis</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $p): ?>
        <tr>
          <td data-label="#"><?= (int)$p['id_pemesanan'] ?></td>
          <td data-label="Kamar"><strong><?= e($p['nomor_kamar']) ?></strong></td>
          <td data-label="Dipesan"><?= e(tgl($p['tanggal_pesan'])) ?></td>
          <td data-label="Kunjungan"><?= e(tgl($p['tanggal_kunjungan'])) ?></td>
          <td data-label="Masuk"><?= e(tgl($p['tanggal_masuk'])) ?></td>
          <td data-label="Jenis"><?= e(ucfirst($p['jenis_sewa'])) ?><?= $p['durasi_bulan'] ? ' (' . (int)$p['durasi_bulan'] . ' bln)' : '' ?></td>
          <td data-label="Status"><?= badge($p['status']) ?></td>
          <td><a class="btn btn-outline btn-sm" href="/pemesanan/<?= (int)$p['id_pemesanan'] ?>">Lihat</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
