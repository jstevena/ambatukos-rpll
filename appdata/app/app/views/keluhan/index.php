<?php $admin = Auth::role() === 'admin'; ?>
<div class="page-head"><div><h1>Keluhan / Perbaikan</h1></div></div>

<?php if (!$admin): ?>
  <div class="card form-wide">
    <h2>Ajukan Keluhan</h2>
    <?php if ($kamar === []): ?>
      <div class="muted">Keluhan hanya dapat diajukan oleh penyewa yang sedang menyewa kamar.</div>
    <?php else: ?>
      <form method="post" action="/keluhan">
        <?= csrf_field() ?>
        <div class="field">
          <label for="id_kamar">Kamar</label>
          <select id="id_kamar" name="id_kamar" required>
            <?php foreach ($kamar as $p): ?><option value="<?= (int)$p['id_kamar'] ?>">Kamar <?= e($p['nomor_kamar']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label for="judul">Judul</label><input type="text" id="judul" name="judul" maxlength="100" value="<?= e(old('judul')) ?>" required></div>
        <div class="field"><label for="deskripsi">Deskripsi</label><textarea id="deskripsi" name="deskripsi" required><?= e(old('deskripsi')) ?></textarea></div>
        <button class="btn" type="submit">Kirim Keluhan</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<div class="card">
  <h2>Daftar Keluhan</h2>
  <?php if ($daftar === []): ?>
    <div class="empty">Belum ada keluhan.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Tanggal</th><?php if ($admin): ?><th>Penyewa</th><?php endif; ?><th>Kamar</th><th>Keluhan</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $x): ?>
        <tr>
          <td data-label="Tanggal"><?= e(tgl($x['tanggal_lapor'])) ?></td>
          <?php if ($admin): ?><td data-label="Penyewa"><?= e($x['nama_penyewa']) ?></td><?php endif; ?>
          <td data-label="Kamar"><?= e($x['nomor_kamar']) ?></td>
          <td data-label="Keluhan"><strong><?= e($x['judul']) ?></strong><div class="small muted"><?= nl2br(e($x['deskripsi'])) ?></div></td>
          <td data-label="Status">
            <?php if ($admin): ?>
              <form method="post" action="/keluhan/<?= (int)$x['id_keluhan'] ?>/status" class="row-actions">
                <?= csrf_field() ?>
                <select name="status" style="width:auto">
                  <?php foreach (['Baru', 'Diproses', 'Selesai'] as $s): ?><option <?= $x['status'] === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
                </select>
                <button class="btn btn-sm" type="submit">Simpan</button>
              </form>
            <?php else: ?>
              <?= badge($x['status'], $x['status'] === 'Selesai' ? 'ok' : null) ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
