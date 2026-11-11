<style>
  .pw-rules{list-style:none;padding:0;margin:6px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:12px;color:var(--muted)}
  .pw-rules li::before{content:"\2717\00a0";color:var(--danger)}
  .pw-rules li.ok{color:var(--ok)}
  .pw-rules li.ok::before{content:"\2713\00a0";color:var(--ok)}
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>

<div class="page-head"><div><h1>Akun Saya</h1></div></div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h2>Informasi Akun</h2>
    <dl class="kv">
      <dt>Nama</dt><dd><?= e($user['nama']) ?></dd>
      <dt>Username</dt><dd><?= e($user['username']) ?></dd>
      <dt>Peran</dt><dd><?= e(['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'][$user['peran']] ?? $user['peran']) ?></dd>
    </dl>
  </div>

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
</div>

<script>
(function () {
  var lama = document.getElementById('password_lama');
  var p = document.getElementById('password'), p2 = document.getElementById('password2');
  var hP2 = document.getElementById('hPw2');
  var rules = document.querySelectorAll('#pwRules li');

  function hint(el, state, msg) { el.className = 'hint' + (state ? ' ' + state : ''); el.textContent = msg; }

  function cekPassword() {
    var v = p.value;
    var r = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) };
    var all = true;
    rules.forEach(function (li) {
      var ok = r[li.getAttribute('data-r')];
      li.classList.toggle('ok', ok);
      if (!ok) all = false;
    });
    var sama = v !== '' && v === lama.value;
    p.setCustomValidity(!all ? 'Password belum memenuhi semua syarat.' : (sama ? 'Password baru harus berbeda dari password saat ini.' : ''));
    cekPassword2();
  }

  function cekPassword2() {
    if (p2.value === '') { hint(hP2, '', ''); p2.setCustomValidity(''); return; }
    var ok = p2.value === p.value;
    hint(hP2, ok ? 'ok' : 'bad', ok ? 'Password cocok.' : 'Password tidak sama.');
    p2.setCustomValidity(ok ? '' : 'Password tidak sama.');
  }

  lama.addEventListener('input', cekPassword);
  p.addEventListener('input', cekPassword);
  p2.addEventListener('input', cekPassword2);
  cekPassword();
})();
</script>
