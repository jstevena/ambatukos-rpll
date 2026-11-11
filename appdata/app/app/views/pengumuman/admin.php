<div class="page-head"><div><h1>Pengumuman Sewa</h1><p class="muted" style="margin:0">Umumkan kenaikan sewa yang ditetapkan pemilik minimal 30 hari sebelum tanggal berlaku.</p></div></div>
<div class="card">
  <?php if ($daftar === []): ?>
    <div class="empty">Belum ada penetapan kenaikan sewa dari pemilik.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Kamar</th><th class="num">Biaya saat ini</th><th class="num">Biaya baru</th><th>Berlaku</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $s): ?>
        <tr>
          <td data-label="Kamar"><strong><?= e($s['nomor_kamar']) ?></strong></td>
          <td data-label="Biaya saat ini" class="num"><?= rupiah($s['biaya_per_bulan']) ?></td>
          <td data-label="Biaya baru" class="num"><?= rupiah($s['biaya_baru']) ?></td>
          <td data-label="Berlaku"><?= e(tgl($s['tanggal_berlaku'])) ?></td>
          <td data-label="Status"><?= badge($s['status']) ?></td>
          <td>
            <?php if ($s['status'] === 'Ditetapkan'): ?>
              <form method="post" action="/admin/pengumuman/<?= (int)$s['id_pengumuman'] ?>/umumkan" data-confirm="Umumkan kenaikan sewa ini ke penyewa terkait?">
                <?= csrf_field() ?><button class="btn btn-sm" type="submit">Umumkan</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
