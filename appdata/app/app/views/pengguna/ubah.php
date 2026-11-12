<style>
  .pw-rules{list-style:none;padding:0;margin:6px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;font-size:12px;color:var(--muted)}
  .pw-rules li::before{content:"\2717\00a0";color:var(--danger)}
  .pw-rules li.ok{color:var(--ok)}
  .pw-rules li.ok::before{content:"\2713\00a0";color:var(--ok)}
  .hint.ok{color:var(--ok)} .hint.bad{color:var(--danger)}
</style>
<?php $label = ['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos']; ?>
<p class="small"><a href="/pengguna">&larr; Pengguna</a></p>
<div class="page-head"><h1>Ubah Akun <?= e($label[$u['peran']] ?? '') ?></h1></div>

<div class="card form-narrow">
  <dl class="kv" style="margin-bottom:16px">
    <dt>NIK</dt><dd><?= e($u['nik'] ?: '-') ?></dd>
    <dt>No. HP</dt><dd><?= e(tampil_hp($u['no_hp'])) ?></dd>
  </dl>
  <form method="post" action="/pengguna/<?= (int)$u['id_pengguna'] ?>/ubah" autocomplete="off">
    <?= csrf_field() ?>
    <div class="field">
      <label for="nama">Nama</label>
      <input type="text" id="nama" name="nama" maxlength="100" value="<?= e(old('nama', $u['nama'])) ?>" required>
    </div>
    <div class="field">
      <label for="username">Username</label>
      <input type="text" id="username" name="username" maxlength="50" value="<?= e(old('username', $u['username'])) ?>" required>
      <div class="hint" id="hUser">4-50 karakter: huruf, angka, titik, garis bawah.</div>
    </div>
    <?php if ($u['bisa_password']): ?>
    <div class="field">
      <label for="password">Password baru <small>(kosongkan jika tidak diubah)</small></label>
      <input type="password" id="password" name="password" maxlength="72" autocomplete="new-password">
      <ul class="pw-rules" id="pwRules">
        <li data-r="len">Minimal 8 karakter</li>
        <li data-r="up">Huruf besar (A-Z)</li>
        <li data-r="low">Huruf kecil (a-z)</li>
        <li data-r="num">Angka (0-9)</li>
      </ul>
    </div>
    <?php endif; ?>
    <div class="btn-row">
      <button class="btn" type="submit">Simpan</button>
      <a class="btn btn-outline" href="/pengguna">Batal</a>
    </div>
  </form>
</div>

<script>
(function () {
  var u = document.getElementById('username'), p = document.getElementById('password');
  var hU = document.getElementById('hUser'), rules = document.querySelectorAll('#pwRules li');
  var DEF_U = hU.textContent;
  function hint(el, s, m) { el.className = 'hint' + (s ? ' ' + s : ''); el.textContent = m; }
  function cekUser() {
    var v = u.value;
    if (v === '') { hint(hU, '', DEF_U); u.setCustomValidity(''); return; }
    var ok = /^[A-Za-z0-9_.]{4,50}$/.test(v);
    var msg = v.length < 4 ? 'Username minimal 4 karakter.' : /[^A-Za-z0-9_.]/.test(v) ? 'Hanya huruf, angka, titik, dan garis bawah.' : 'Username valid.';
    hint(hU, ok ? 'ok' : 'bad', msg); u.setCustomValidity(ok ? '' : msg);
  }
  function cekPw() {
    if (!p) return;
    var v = p.value, r = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) }, all = true;
    rules.forEach(function (li) { var ok = r[li.getAttribute('data-r')]; li.classList.toggle('ok', ok); if (!ok) all = false; });
    p.setCustomValidity(v === '' || all ? '' : 'Password belum memenuhi semua syarat.');
  }
  u.addEventListener('input', cekUser); if (p) p.addEventListener('input', cekPw);
  cekUser(); cekPw();
})();
</script>
