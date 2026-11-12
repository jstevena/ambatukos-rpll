<style>
  .pw-rules{list-style:none;padding:0;margin:6px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:12px;color:var(--muted)}
  .pw-rules li::before{content:"\2717\00a0";color:var(--danger)}
  .pw-rules li.ok{color:var(--ok)}
  .pw-rules li.ok::before{content:"\2713\00a0";color:var(--ok)}
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>
<p class="small"><a href="/pengguna">&larr; Pengguna</a></p>
<div class="page-head"><h1>Tambah Pengguna</h1></div>

<div class="card form-wide">
  <form method="post" action="/pengguna" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="peran">Peran</label>
      <select id="peran" name="peran" required>
        <?php foreach ($peranBoleh as $v => $l): ?>
          <option value="<?= e($v) ?>" <?= old('peran', 'penyewa') === $v ? 'selected' : '' ?>><?= e($l) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
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
        <label for="no_hp">Nomor HP <small class="req-penyewa">(wajib untuk penyewa)</small></label>
        <input type="text" data-hp id="no_hp" name="no_hp" inputmode="tel" maxlength="20" value="<?= e(old('no_hp')) ?>">
        <div class="hint">Boleh diawali 08 atau +62, 9-13 digit (format 08xx). Disimpan sebagai +62.</div>
      </div>
    </div>
    <div class="field">
      <label for="nik">NIK <small class="req-penyewa">(wajib untuk penyewa)</small></label>
      <input type="text" id="nik" name="nik" inputmode="numeric" maxlength="16" value="<?= e(old('nik')) ?>">
      <div class="hint" id="hNik">16 digit angka sesuai KTP.</div>
    </div>
    <div class="cols-2">
      <div class="field">
        <label for="password">Password</label>
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
    </div>
    <div class="btn-row">
      <button class="btn" type="submit">Buat Akun</button>
      <a class="btn btn-outline" href="/pengguna">Batal</a>
    </div>
  </form>
</div>

<script>
(function () {
  var peran = document.getElementById('peran'), u = document.getElementById('username'), n = document.getElementById('nik');
  var hp = document.getElementById('no_hp');
  var p = document.getElementById('password'), p2 = document.getElementById('password2');
  var hU = document.getElementById('hUser'), hN = document.getElementById('hNik'), hP2 = document.getElementById('hPw2');
  var rules = document.querySelectorAll('#pwRules li');
  var DEF_U = hU.textContent, DEF_N = hN.textContent;
  function hint(el, s, m) { el.className = 'hint' + (s ? ' ' + s : ''); el.textContent = m; }

  function syncPeran() {
    var wajib = peran.value === 'penyewa';
    [hp, n].forEach(function (el) { el.required = wajib; });
    document.querySelectorAll('.req-penyewa').forEach(function (s) { s.style.display = wajib ? '' : 'none'; });
  }
  function cekUser() {
    var v = u.value;
    if (v === '') { hint(hU, '', DEF_U); u.setCustomValidity(''); return; }
    var ok = /^[A-Za-z0-9_.]{4,50}$/.test(v);
    var msg = v.length < 4 ? 'Username minimal 4 karakter.' : /[^A-Za-z0-9_.]/.test(v) ? 'Hanya huruf, angka, titik, dan garis bawah.' : 'Username valid.';
    hint(hU, ok ? 'ok' : 'bad', msg); u.setCustomValidity(ok ? '' : msg);
  }
  function cekNik() {
    n.value = n.value.replace(/\D/g, '').slice(0, 16);
    var len = n.value.length;
    if (len === 0) { hint(hN, '', DEF_N); n.setCustomValidity(''); return; }
    var ok = len === 16, msg = ok ? 'NIK valid (16 digit).' : 'NIK harus 16 digit (' + len + '/16).';
    hint(hN, ok ? 'ok' : 'bad', msg); n.setCustomValidity(ok ? '' : msg);
  }
  function cekPw() {
    var v = p.value, r = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) }, all = true;
    rules.forEach(function (li) { var ok = r[li.getAttribute('data-r')]; li.classList.toggle('ok', ok); if (!ok) all = false; });
    p.setCustomValidity(all ? '' : 'Password belum memenuhi semua syarat.'); cekPw2();
  }
  function cekPw2() {
    if (p2.value === '') { hint(hP2, '', ''); p2.setCustomValidity(''); return; }
    var ok = p2.value === p.value;
    hint(hP2, ok ? 'ok' : 'bad', ok ? 'Password cocok.' : 'Password tidak sama.'); p2.setCustomValidity(ok ? '' : 'Password tidak sama.');
  }
  peran.addEventListener('change', syncPeran); u.addEventListener('input', cekUser); n.addEventListener('input', cekNik);
  p.addEventListener('input', cekPw); p2.addEventListener('input', cekPw2);
  syncPeran(); cekUser(); cekNik(); cekPw();
})();
</script>
