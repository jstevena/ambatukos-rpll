<style>
  .pw-rules{list-style:none;padding:0;margin:6px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:12px;color:var(--muted)}
  .pw-rules li::before{content:"\2717\00a0";color:var(--danger)}
  .pw-rules li.ok{color:var(--ok)}
  .pw-rules li.ok::before{content:"\2713\00a0";color:var(--ok)}
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>
<?php $bisaUsername = in_array($user['peran'], ['admin', 'pemilik'], true); ?>

<div class="page-head"><div><h1>Akun Saya</h1></div></div>

<div class="grid grid-2" style="align-items:start">
  <div>
    <div class="card">
      <h2>Informasi Akun</h2>
      <dl class="kv">
        <dt>Nama</dt><dd><?= e($user['nama']) ?></dd>
        <dt>Username</dt><dd><?= e($user['username']) ?></dd>
        <dt>Peran</dt><dd><?= e(['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'][$user['peran']] ?? $user['peran']) ?></dd>
      </dl>
      <?php if (!$bisaUsername): ?><p class="muted small" style="margin-bottom:0">Username tidak dapat diubah sendiri. Hubungi admin bila perlu menggantinya.</p><?php endif; ?>
    </div>

    <div class="card">
      <h2>Ubah Nama</h2>
      <form method="post" action="/akun/nama" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
          <label for="nama">Nama lengkap</label>
          <input type="text" id="nama" name="nama" maxlength="100" value="<?= e(old('nama', $user['nama'])) ?>" required>
        </div>
        <button class="btn" type="submit">Simpan Nama</button>
      </form>
    </div>

    <?php $hpTampil = ($noHp ?? '') !== '' ? tampil_hp($noHp) : ''; ?>
    <div class="card">
      <h2>Ubah Nomor HP</h2>
      <form method="post" action="/akun/hp" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
          <label for="no_hp">Nomor HP</label>
          <input type="text" data-hp id="no_hp" name="no_hp" inputmode="tel" maxlength="16" value="<?= e(old('no_hp', $hpTampil)) ?>" required>
          <div class="hint">Boleh diawali 08 atau +62, 9-13 digit (format 08xx). Disimpan sebagai +62.</div>
        </div>
        <button class="btn" type="submit">Simpan Nomor HP</button>
      </form>
    </div>

    <?php if ($bisaUsername): ?>
    <div class="card">
      <h2>Ubah Username</h2>
      <form method="post" action="/akun/username" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
          <label for="username">Username baru</label>
          <input type="text" id="username" name="username" maxlength="50" value="<?= e(old('username', $user['username'])) ?>" required>
          <div class="hint" id="hUser">4-50 karakter: huruf, angka, titik, garis bawah.</div>
        </div>
        <div class="field">
          <label for="pw_user">Password saat ini</label>
          <input type="password" id="pw_user" name="password_lama" autocomplete="current-password" required>
        </div>
        <button class="btn" type="submit">Simpan Username</button>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if (!empty($passwordDiatur)): ?>
    <div class="card">
      <h2>Ubah Password</h2>
      <form method="post" action="/akun/password" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
          <label for="password_lama">Password saat ini</label>
          <input type="password" id="password_lama" name="password_lama" autocomplete="current-password" required>
        </div>
        <div class="field">
          <label for="password">Password baru</label>
          <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password" required>
          <ul class="pw-rules" id="pwRules">
            <li data-r="len">Minimal 8 karakter</li>
            <li data-r="up">Huruf besar (A-Z)</li>
            <li data-r="low">Huruf kecil (a-z)</li>
            <li data-r="num">Angka (0-9)</li>
          </ul>
        </div>
        <div class="field">
          <label for="password2">Ulangi password baru</label>
          <input type="password" id="password2" name="password2" maxlength="72" autocomplete="new-password" required>
          <div class="hint" id="hPw2"></div>
        </div>
        <button class="btn" type="submit">Simpan Password</button>
      </form>
    </div>
    <?php else: ?>
    <div class="card">
      <h2>Atur Password</h2>
      <p class="muted">Anda masuk lewat Google dan belum mengatur password. Atur password agar bisa masuk dengan email dan password juga.</p>
      <form method="post" action="/akun/atur-password" autocomplete="off">
        <?= csrf_field() ?>
        <div class="field">
          <label for="password">Password baru</label>
          <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password" required>
          <ul class="pw-rules" id="pwRules">
            <li data-r="len">Minimal 8 karakter</li>
            <li data-r="up">Huruf besar (A-Z)</li>
            <li data-r="low">Huruf kecil (a-z)</li>
            <li data-r="num">Angka (0-9)</li>
          </ul>
        </div>
        <div class="field">
          <label for="password2">Ulangi password</label>
          <input type="password" id="password2" name="password2" maxlength="72" autocomplete="new-password" required>
          <div class="hint" id="hPw2"></div>
        </div>
        <button class="btn" type="submit">Simpan Password</button>
      </form>
    </div>
    <?php endif; ?>

    <?php if ($user['peran'] === 'penyewa' && !empty($googleAktif)): ?>
    <div class="card">
      <h2>Akun Google</h2>
      <?php if ($google['terhubung']): ?>
        <p style="margin:0">Terhubung dengan <strong><?= e($google['email']) ?></strong>. Anda dapat masuk dengan tombol <em>Masuk dengan Google</em>.</p>
      <?php else: ?>
        <p class="muted">Hubungkan akun Google agar bisa masuk tanpa perlu mengingat password.</p>
        <a class="btn btn-outline" href="/auth/google?mode=tautkan">Hubungkan Google</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
(function () {
  var lama = document.getElementById('password_lama');
  var p = document.getElementById('password'), p2 = document.getElementById('password2');
  var hP2 = document.getElementById('hPw2');
  var rules = document.querySelectorAll('#pwRules li');
  var u = document.getElementById('username'), hU = document.getElementById('hUser');

  function hint(el, state, msg) { el.className = 'hint' + (state ? ' ' + state : ''); el.textContent = msg; }

  if (u && hU) {
    var DEF_U = hU.textContent;
    var cekUser = function () {
      var v = u.value;
      if (v === '') { hint(hU, '', DEF_U); u.setCustomValidity(''); return; }
      var ok = /^[A-Za-z0-9_.]{4,50}$/.test(v);
      var msg = v.length < 4 ? 'Username minimal 4 karakter.' : /[^A-Za-z0-9_.]/.test(v) ? 'Hanya huruf, angka, titik, dan garis bawah.' : 'Username valid.';
      hint(hU, ok ? 'ok' : 'bad', msg); u.setCustomValidity(ok ? '' : msg);
    };
    u.addEventListener('input', cekUser); cekUser();
  }

  function cekPassword() {
    var v = p.value;
    var r = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) };
    var all = true;
    rules.forEach(function (li) { var ok = r[li.getAttribute('data-r')]; li.classList.toggle('ok', ok); if (!ok) all = false; });
    var sama = lama && v !== '' && v === lama.value;
    p.setCustomValidity(!all ? 'Password belum memenuhi semua syarat.' : (sama ? 'Password baru harus berbeda dari password saat ini.' : ''));
    cekPassword2();
  }
  function cekPassword2() {
    if (p2.value === '') { hint(hP2, '', ''); p2.setCustomValidity(''); return; }
    var ok = p2.value === p.value;
    hint(hP2, ok ? 'ok' : 'bad', ok ? 'Password cocok.' : 'Password tidak sama.');
    p2.setCustomValidity(ok ? '' : 'Password tidak sama.');
  }
  if (lama) lama.addEventListener('input', cekPassword);
  p.addEventListener('input', cekPassword); p2.addEventListener('input', cekPassword2);
  cekPassword();
})();
</script>
