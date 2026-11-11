<div class="page-head"><div><h1>Verifikasi Pembayaran</h1></div></div>

<div class="card">
  <h2>Menunggu Verifikasi (<?= count($menunggu) ?>)</h2>
  <?php if ($menunggu === []): ?>
    <div class="empty">Tidak ada pembayaran yang menunggu verifikasi.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Dibayar</th><th>Penyewa</th><th>Kamar</th><th>Jenis</th><th class="num">Jumlah</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($menunggu as $x): ?>
        <tr>
          <td data-label="Dibayar"><?= e(tgl($x['tanggal_bayar'], true)) ?></td>
          <td data-label="Penyewa"><?= e($x['nama_penyewa']) ?></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Jenis"><?= e($x['jenis']) ?><?= $x['periode_ke'] ? ' #' . (int)$x['periode_ke'] : '' ?></td>
          <td data-label="Jumlah" class="num"><?= rupiah($x['jumlah']) ?></td>
          <td><a class="btn btn-sm" href="/admin/verifikasi/<?= (int)$x['id_pembayaran'] ?>">Tinjau</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Pengembalian DP (<?= count($pengembalian) ?>)</h2>
  <?php if ($pengembalian === []): ?>
    <div class="empty">Tidak ada DP yang perlu dikembalikan.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Penyewa</th><th>Kamar</th><th class="num">DP</th><th>Alasan</th><th>Catatan & aksi</th></tr></thead>
      <tbody>
      <?php foreach ($pengembalian as $x): ?>
        <tr>
          <td data-label="Penyewa"><?= e($x['nama_penyewa']) ?><div class="small muted"><?= e($x['no_hp']) ?></div></td>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="DP" class="num"><?= rupiah($x['jumlah']) ?></td>
          <td data-label="Alasan"><?= e($x['alasan_batal']) ?></td>
          <td>
            <form method="post" action="/admin/pengembalian/<?= (int)$x['id_pembayaran'] ?>" data-confirm="Tandai DP sudah dikembalikan ke penyewa?">
              <?= csrf_field() ?>
              <input type="text" name="catatan" maxlength="255" placeholder="Catatan (mis. transfer ke BCA)" style="margin-bottom:6px">
              <button class="btn btn-sm" type="submit">Tandai Dikembalikan</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
