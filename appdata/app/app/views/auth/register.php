<style>
  .g-divider{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:12px;margin:16px 0}
  .g-divider::before,.g-divider::after{content:"";flex:1;height:1px;background:var(--line)}
  .btn-google{display:flex;align-items:center;justify-content:center;gap:10px;padding:10px 16px;border-radius:999px;background:var(--surface-solid);color:var(--text);border:1px solid var(--line);text-decoration:none;font-weight:600;box-shadow:var(--shadow-sm)}
  .btn-google svg{width:18px;height:18px}
</style>
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
        <div class="hint">Boleh diawali 08 atau +62. Disimpan sebagai +62.</div>
      </div>
    </div>
    <div class="field">
      <label for="nik">NIK</label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e(old('nik')) ?>" required>
      <div class="hint" id="hNik">16 digit angka sesuai KTP.</div>
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
  <?php if (!empty($googleAktif)): ?>
    <div class="g-divider"><span>atau</span></div>
    <a class="btn-google" href="/auth/google">
      <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
      Daftar dengan Google
    </a>
  <?php endif; ?>
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
