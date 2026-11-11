<style>
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>
<div class="card auth-card" style="max-width:520px">
  <h1>Lengkapi Data</h1>
  <p class="muted">Akun Google <strong><?= e($p['email']) ?></strong> terverifikasi. Lengkapi data berikut untuk menyelesaikan pendaftaran sebagai penyewa.</p>
  <form method="post" action="/daftar/google" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nama">Nama lengkap</label>
      <input type="text" id="nama" name="nama" maxlength="100" value="<?= e(old('nama', $p['nama'])) ?>" required>
    </div>
    <div class="field">
      <label for="no_hp">Nomor HP</label>
      <input type="text" id="no_hp" name="no_hp" inputmode="tel" maxlength="20" value="<?= e(old('no_hp')) ?>" required>
    </div>
    <div class="field">
      <label for="nik">NIK</label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e(old('nik')) ?>" required>
      <div class="hint" id="hNik">16 digit angka sesuai KTP.</div>
    </div>
    <button class="btn" type="submit" style="width:100%">Selesaikan Pendaftaran</button>
  </form>
  <p class="small" style="margin-top:14px"><a href="/daftar">Batal</a></p>
</div>
<script>
(function () {
  var n = document.getElementById('nik'), h = document.getElementById('hNik'), DEF = h.textContent;
  function cek() {
    n.value = n.value.replace(/\D/g, '').slice(0, 16);
    var l = n.value.length;
    if (l === 0) { h.className = 'hint'; h.textContent = DEF; n.setCustomValidity(''); return; }
    var ok = l === 16, m = ok ? 'NIK valid (16 digit).' : 'NIK harus 16 digit (' + l + '/16).';
    h.className = 'hint ' + (ok ? 'ok' : 'bad'); h.textContent = m; n.setCustomValidity(ok ? '' : m);
  }
  n.addEventListener('input', cek); cek();
})();
</script>
