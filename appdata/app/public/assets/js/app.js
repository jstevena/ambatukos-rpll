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
})();
