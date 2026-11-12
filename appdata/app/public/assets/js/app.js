(function () {
  var root = document.documentElement;

  // ---- Tema: default mengikuti browser, tombol untuk mengganti (disimpan di localStorage) ----
  function saved() { try { return localStorage.getItem('theme'); } catch (e) { return null; } }
  function apply(t) { root.setAttribute('data-theme', t); }
  var btn = document.getElementById('themeToggle');
  if (btn) {
    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem('theme', next); } catch (e) {}
      apply(next);
    });
  }
  // Selama belum memilih manual, ikuti perubahan tema sistem/browser secara langsung
  if (window.matchMedia) {
    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    var onChange = function (e) { if (!saved()) apply(e.matches ? 'dark' : 'light'); };
    if (mq.addEventListener) mq.addEventListener('change', onChange);
    else if (mq.addListener) mq.addListener(onChange);
  }

  // ---- Menu pil otomatis jadi drawer bila tidak muat di header ----
  var inner = document.querySelector('.topbar-inner');
  var pill = document.querySelector('.pillnav');
  function cekMenu() {
    if (!inner || !pill) return;
    document.body.classList.remove('nav-collapsed');
    if (inner.scrollWidth > inner.clientWidth + 1) document.body.classList.add('nav-collapsed');
  }
  cekMenu();
  window.addEventListener('resize', cekMenu);
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(cekMenu);

  // ---- Drawer menu pada tablet ----
  var t = document.getElementById('menuToggle');
  if (t) t.addEventListener('click', function () { document.body.classList.toggle('menu-open'); });
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('menu-open') && !e.target.closest('.sidebar') && !e.target.closest('#menuToggle')) {
      document.body.classList.remove('menu-open');
    }
  });

  // ---- Konfirmasi aksi penting: <form data-confirm="Yakin?"> ----
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // ---- Form pesan kamar: durasi aktif hanya untuk sewa sementara (BR-11) ----
  var radios = document.querySelectorAll('input[name="jenis_sewa"]');
  var durasi = document.getElementById('durasi_bulan');
  function syncDurasi() {
    if (!durasi || !radios.length) return;
    var v = (document.querySelector('input[name="jenis_sewa"]:checked') || {}).value;
    durasi.disabled = v !== 'sementara';
    durasi.required = v === 'sementara';
    if (v !== 'sementara') durasi.value = '';
  }
  radios.forEach(function (r) { r.addEventListener('change', syncDurasi); });
  syncDurasi();

  // ---- Tanggal kunjungan <= tanggal masuk (BR-03) ----
  var kunj = document.getElementById('tanggal_kunjungan');
  var masuk = document.getElementById('tanggal_masuk');
  function syncTanggal() {
    if (!kunj || !masuk) return;
    if (kunj.value) masuk.min = kunj.value;
    if (masuk.value) kunj.max = masuk.value;
    kunj.setCustomValidity(kunj.value && masuk.value && kunj.value > masuk.value ? 'Tanggal kunjungan harus sebelum atau sama dengan tanggal masuk.' : '');
  }
  if (kunj && masuk) { kunj.addEventListener('change', syncTanggal); masuk.addEventListener('change', syncTanggal); syncTanggal(); }

  // ---- Tombol lihat/sembunyikan password pada semua kolom password ----
  var st = document.createElement('style');
  st.textContent = '.pw-wrap{position:relative}.pw-wrap input{padding-right:46px}'
    + '.pw-toggle{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;background:transparent;color:var(--muted);cursor:pointer;border-radius:50%;display:flex;align-items:center;justify-content:center;padding:0}'
    + '.pw-toggle:hover{color:var(--accent);background:var(--accent-soft)}'
    + '.pw-toggle svg{width:18px;height:18px;stroke:currentColor;fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}'
    + '.pw-toggle .eye-off{display:none}.pw-toggle.show .eye{display:none}.pw-toggle.show .eye-off{display:block}';
  document.head.appendChild(st);
  var ICON = '<svg class="eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>'
    + '<svg class="eye-off" viewBox="0 0 24 24"><path d="M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.2 4M6.5 6.5C3.8 8.3 2 12 2 12s3.5 7 10 7c1.6 0 3-.4 4.3-1M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
  document.querySelectorAll('input[type="password"]').forEach(function (inp) {
    var wrap = document.createElement('div');
    wrap.className = 'pw-wrap';
    inp.parentNode.insertBefore(wrap, inp);
    wrap.appendChild(inp);
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'pw-toggle';
    b.setAttribute('aria-label', 'Tampilkan password');
    b.innerHTML = ICON;
    b.addEventListener('click', function () {
      var tampil = inp.type === 'password';
      inp.type = tampil ? 'text' : 'password';
      b.classList.toggle('show', tampil);
      b.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
    });
    wrap.appendChild(b);
  });
})();
