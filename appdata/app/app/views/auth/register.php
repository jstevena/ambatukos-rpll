<style>
  .pw-rules{list-style:none;padding:0;margin:6px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:12px;color:var(--muted)}
  .pw-rules li::before{content:"\2717\00a0";color:var(--danger)}
  .pw-rules li.ok{color:var(--ok)}
  .pw-rules li.ok::before{content:"\2713\00a0";color:var(--ok)}
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>

<div class="card auth-card" style="max-width:560px">
  <h1>Daftar Akun</h1>
  <p class="muted">Buat akun untuk memesan kamar dan membayar sewa secara online.</p>
  <form method="post" action="/daftar" autocomplete="off" id="formDaftar">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nama">Nama lengkap</label>
      <input type="text" id="nama" name="nama" maxlength="100" value="<?= e(old('nama')) ?>" required>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" maxlength="50" value="<?= e(old('username')) ?>" required>
        <div class="hint" id="hUser">4-50 karakter: huruf, angka, titik, garis bawah.</div>
      </div>
      <div class="field">
        <label for="no_hp">Nomor HP</label>
        <input type="text" id="no_hp" name="no_hp" inputmode="tel" maxlength="20" value="<?= e(old('no_hp')) ?>" required>
      </div>
    </div>
    <div class="field">
      <label for="nik">NIK</label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e(old('nik')) ?>" required>
      <div class="hint" id="hNik">16 digit angka sesuai KTP.</div>
    </div>
    <div class="field">
      <label for="alamat">Alamat</label>
      <input type="text" id="alamat" name="alamat" maxlength="255" value="<?= e(old('alamat')) ?>" required>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" maxlength="72" required>
        <ul class="pw-rules" id="pwRules">
          <li data-r="len">Minimal 8 karakter</li>
          <li data-r="up">Huruf besar (A-Z)</li>
          <li data-r="low">Huruf kecil (a-z)</li>
          <li data-r="num">Angka (0-9)</li>
        </ul>
      </div>
      <div class="field">
        <label for="password2">Ulangi password</label>
        <input type="password" id="password2" name="password2" maxlength="72" required>
        <div class="hint" id="hPw2"></div>
      </div>
    </div>
    <button class="btn" type="submit" style="width:100%">Daftar</button>
  </form>
  <p class="small" style="margin-top:14px">Sudah punya akun? <a href="/login">Masuk</a></p>
</div>

<script>
(function () {
  var u = document.getElementById('username'), n = document.getElementById('nik');
  var p = document.getElementById('password'), p2 = document.getElementById('password2');
  var hU = document.getElementById('hUser'), hN = document.getElementById('hNik'), hP2 = document.getElementById('hPw2');
  var rules = document.querySelectorAll('#pwRules li');
  var DEF_U = hU.textContent, DEF_N = hN.textContent;

  function hint(el, state, msg) { el.className = 'hint' + (state ? ' ' + state : ''); el.textContent = msg; }

  function cekUser() {
    var v = u.value;
    if (v === '') { hint(hU, '', DEF_U); u.setCustomValidity(''); return; }
    var ok = /^[A-Za-z0-9_.]{4,50}$/.test(v);
    var msg = v.length < 4 ? 'Username minimal 4 karakter.'
      : /[^A-Za-z0-9_.]/.test(v) ? 'Hanya huruf, angka, titik, dan garis bawah.'
      : 'Username valid.';
    hint(hU, ok ? 'ok' : 'bad', msg);
    u.setCustomValidity(ok ? '' : msg);
  }

  function cekNik() {
    n.value = n.value.replace(/\D/g, '').slice(0, 16);   // hanya angka, maksimal 16
    var len = n.value.length;
    if (len === 0) { hint(hN, '', DEF_N); n.setCustomValidity(''); return; }
    var ok = len === 16;
    var msg = ok ? 'NIK valid (16 digit).' : 'NIK harus 16 digit (' + len + '/16).';
    hint(hN, ok ? 'ok' : 'bad', msg);
    n.setCustomValidity(ok ? '' : msg);
  }

  function cekPassword() {
    var v = p.value;
    var r = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) };
    var all = true;
    rules.forEach(function (li) {
      var ok = r[li.getAttribute('data-r')];
      li.classList.toggle('ok', ok);
      if (!ok) all = false;
    });
    p.setCustomValidity(all ? '' : 'Password belum memenuhi semua syarat.');
    cekPassword2();
  }

  function cekPassword2() {
    if (p2.value === '') { hint(hP2, '', ''); p2.setCustomValidity(''); return; }
    var ok = p2.value === p.value;
    hint(hP2, ok ? 'ok' : 'bad', ok ? 'Password cocok.' : 'Password tidak sama.');
    p2.setCustomValidity(ok ? '' : 'Password tidak sama.');
  }

  u.addEventListener('input', cekUser);
  n.addEventListener('input', cekNik);
  p.addEventListener('input', cekPassword);
  p2.addEventListener('input', cekPassword2);
  cekUser(); cekNik(); cekPassword();
})();
</script>
