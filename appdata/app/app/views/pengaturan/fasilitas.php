<div class="page-head"><div><h1>Fasilitas Umum</h1><p class="muted" style="margin:0">Tampil di halaman Daftar Kamar, di atas daftar kamar. Fasilitas khusus tiap kamar diatur di Kelola Kamar.</p></div></div>
<div class="card form-wide">
  <form method="post" action="/pemilik/fasilitas" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="fasilitas_info">Keterangan singkat</label>
      <input type="text" id="fasilitas_info" name="fasilitas_info" maxlength="200" value="<?= e(old('fasilitas_info', $v['fasilitas_info'])) ?>" placeholder="Rumah kost baru, 2 lantai">
    </div>
    <div class="field">
      <label for="fasilitas_umum">Fasilitas umum</label>
      <textarea id="fasilitas_umum" name="fasilitas_umum" rows="14" maxlength="3000"><?= e(old('fasilitas_umum', $v['fasilitas_umum'])) ?></textarea>
      <div class="hint">Satu fasilitas per baris. Baris yang diawali <code>#</code> menjadi judul kelompok, contoh: <code># Keamanan &amp; parkir</code>.</div>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="harga_termasuk">Termasuk harga sewa</label>
        <textarea id="harga_termasuk" name="harga_termasuk" rows="5" maxlength="1000"><?= e(old('harga_termasuk', $v['harga_termasuk'])) ?></textarea>
        <div class="hint">Satu per baris.</div>
      </div>
      <div class="field">
        <label for="harga_tidak_termasuk">Tidak termasuk</label>
        <textarea id="harga_tidak_termasuk" name="harga_tidak_termasuk" rows="5" maxlength="1000"><?= e(old('harga_tidak_termasuk', $v['harga_tidak_termasuk'])) ?></textarea>
        <div class="hint">Satu per baris.</div>
      </div>
    </div>
    <div class="btn-row"><button class="btn" type="submit">Simpan</button><a class="btn btn-outline" href="/kamar" target="_blank" rel="noopener">Lihat hasilnya</a></div>
  </form>
</div>
