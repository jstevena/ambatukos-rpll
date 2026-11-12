<?php
$edit = $k !== null;
$val = static fn(string $f, mixed $def = '') => old($f, $edit ? ($k[$f] ?? $def) : $def);
$luas = $edit ? rtrim(rtrim((string)$k['luas'], '0'), '.') : '';
?>
<p class="small"><a href="/admin/kamar">&larr; Kelola kamar</a></p>
<div class="page-head"><h1><?= $edit ? 'Ubah Kamar ' . e($k['nomor_kamar']) : 'Tambah Kamar' ?></h1></div>

<div class="card form-wide">
  <form method="post" enctype="multipart/form-data" action="<?= $edit ? '/admin/kamar/' . (int)$k['id_kamar'] : '/admin/kamar' ?>">
    <?= csrf_field() ?>
    <div class="cols-2">
      <div class="field">
        <label for="nomor_kamar">Nomor kamar</label>
        <input type="text" id="nomor_kamar" name="nomor_kamar" maxlength="10" value="<?= e(old('nomor_kamar', $edit ? $k['nomor_kamar'] : '')) ?>" required>
      </div>
      <div class="field">
        <label for="luas">Luas (m&sup2;)</label>
        <input type="number" id="luas" name="luas" min="1" max="999" step="0.01" value="<?= e(old('luas', $luas)) ?>" required>
      </div>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="biaya_per_bulan">Biaya sewa per bulan (Rp)</label>
        <input type="text" inputmode="numeric" data-rupiah id="biaya_per_bulan" name="biaya_per_bulan" value="<?= e(old('biaya_per_bulan', $edit ? (int)$k['biaya_per_bulan'] : '')) ?>" required>
      </div>
      <div class="field">
        <label for="biaya_per_tahun">Biaya sewa per tahun (Rp)</label>
        <input type="text" inputmode="numeric" data-rupiah id="biaya_per_tahun" name="biaya_per_tahun" value="<?= e(old('biaya_per_tahun', $edit && $k['biaya_per_tahun'] !== null ? (int)$k['biaya_per_tahun'] : '')) ?>">
        <div class="hint">Boleh dikosongkan.</div>
      </div>
    </div>
    <div class="field">
      <label for="jenis_sewa">Jenis sewa</label>
      <select id="jenis_sewa" name="jenis_sewa" required>
        <?php $js = old('jenis_sewa', $edit ? $k['jenis_sewa'] : 'tetap'); foreach (['tetap' => 'Tetap', 'sementara' => 'Sementara'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $js === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label for="fasilitas">Fasilitas</label>
      <textarea id="fasilitas" name="fasilitas" placeholder="Pisahkan dengan koma, mis. Kasur, Lemari, AC"><?= e(old('fasilitas', $edit ? $k['fasilitas'] : '')) ?></textarea>
    </div>
    <div class="field">
      <label for="foto">Foto kamar</label>
      <input type="file" id="foto" name="foto[]" accept="image/jpeg,image/png,image/webp" multiple>
      <div class="hint">JPG, PNG, atau WebP, maksimal 2 MB per foto (maks. 10 foto sekali unggah).</div>
    </div>
    <div class="btn-row">
      <button class="btn" type="submit">Simpan</button>
      <a class="btn btn-outline" href="/admin/kamar">Batal</a>
    </div>
  </form>
</div>

<?php if ($edit && $k['fotos'] !== []): ?>
  <div class="card form-wide">
    <h2>Foto saat ini</h2>
    <div class="gallery">
      <?php foreach ($k['fotos'] as $f): ?>
        <div class="thumb-edit">
          <img src="<?= e(foto_url($f['path_file'])) ?>" alt="Foto kamar">
          <form method="post" action="/admin/kamar/foto/<?= (int)$f['id_foto'] ?>/hapus" data-confirm="Hapus foto ini?">
            <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Hapus</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
