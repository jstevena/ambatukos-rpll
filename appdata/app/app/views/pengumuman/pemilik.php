<div class="page-head"><div><h1>Kenaikan Sewa</h1><p class="muted" style="margin:0">Tetapkan biaya sewa baru dan umumkan langsung kepada penyewa, atau serahkan pengumumannya ke admin.</p></div></div>
<div class="card form-narrow">
  <h2>Tetapkan Kenaikan</h2>
  <form method="post" action="/pemilik/kenaikan-sewa">
    <?= csrf_field() ?>
    <div class="field">
      <label for="id_kamar">Kamar</label>
      <select id="id_kamar" name="id_kamar" required>
        <option value="">Pilih kamar</option>
        <?php foreach ($kamar as $k): ?>
          <option value="<?= (int)$k['id_kamar'] ?>" <?= (string)old('id_kamar') === (string)$k['id_kamar'] ? 'selected' : '' ?>>Kamar <?= e($k['nomor_kamar']) ?> (saat ini <?= e(rupiah($k['biaya_per_bulan'])) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="biaya_baru">Biaya baru per bulan</label>
      <input type="text" inputmode="numeric" data-rupiah id="biaya_baru" name="biaya_baru" value="<?= e(old('biaya_baru')) ?>" placeholder="Rp1.600.000" required>
    </div>
    <div class="field">
      <label for="tanggal_berlaku">Tanggal berlaku</label>
      <input type="date" id="tanggal_berlaku" name="tanggal_berlaku" min="<?= e($minTanggal) ?>" value="<?= e(old('tanggal_berlaku')) ?>" required>
      <div class="hint">Minimal 30 hari dari hari ini (paling cepat <?= e(tgl($minTanggal)) ?>).</div>
    </div>
    <div class="btn-row">
      <button class="btn" type="submit" name="aksi" value="umumkan" onclick="return confirm('Tetapkan dan umumkan sekarang? Penyewa aktif akan langsung diberi notifikasi.');">Tetapkan &amp; Umumkan</button>
      <button class="btn btn-outline" type="submit" name="aksi" value="tetapkan">Simpan saja (diumumkan admin)</button>
    </div>
  </form>
</div>

<div class="card">
  <h2>Riwayat Penetapan</h2>
  <?php if ($daftar === []): ?>
    <div class="empty">Belum ada penetapan.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Kamar</th><th class="num">Biaya baru</th><th>Berlaku</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $s): ?>
        <tr>
          <td data-label="Kamar"><?= e($s['nomor_kamar']) ?></td>
          <td data-label="Biaya baru" class="num"><?= rupiah($s['biaya_baru']) ?></td>
          <td data-label="Berlaku"><?= e(tgl($s['tanggal_berlaku'])) ?></td>
          <td data-label="Status"><?= badge($s['status']) ?></td>
          <td>
            <?php if ($s['status'] === 'Ditetapkan'): ?>
              <form method="post" action="/pemilik/kenaikan-sewa/<?= (int)$s['id_pengumuman'] ?>/umumkan" data-confirm="Umumkan kenaikan sewa ini sekarang ke penyewa terkait?">
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
