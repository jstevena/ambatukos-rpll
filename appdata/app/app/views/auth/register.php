<div class="card auth-card" style="max-width:560px">
  <h1>Daftar Akun</h1>
  <p class="muted">Buat akun untuk memesan kamar dan membayar sewa secara online.</p>
  <form method="post" action="/daftar" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nama">Nama lengkap</label>
      <input type="text" id="nama" name="nama" maxlength="100" value="<?= e(old('nama')) ?>" required>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" maxlength="50" value="<?= e(old('username')) ?>" required>
        <div class="hint">4-50 karakter: huruf, angka, titik, garis bawah.</div>
      </div>
      <div class="field">
        <label for="no_hp">Nomor HP</label>
        <input type="text" id="no_hp" name="no_hp" inputmode="tel" maxlength="20" value="<?= e(old('no_hp')) ?>" required>
      </div>
    </div>
    <div class="field">
      <label for="nik">NIK</label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" pattern="[0-9]{16}" value="<?= e(old('nik')) ?>" required>
      <div class="hint">16 digit sesuai KTP.</div>
    </div>
    <div class="field">
      <label for="alamat">Alamat</label>
      <input type="text" id="alamat" name="alamat" maxlength="255" value="<?= e(old('alamat')) ?>" required>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" minlength="6" required>
      </div>
      <div class="field">
        <label for="password2">Ulangi password</label>
        <input type="password" id="password2" name="password2" minlength="6" required>
      </div>
    </div>
    <button class="btn" type="submit" style="width:100%">Daftar</button>
  </form>
  <p class="small" style="margin-top:14px">Sudah punya akun? <a href="/login">Masuk</a></p>
</div>
