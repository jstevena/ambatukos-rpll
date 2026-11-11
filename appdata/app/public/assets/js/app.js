(function () {
  // Sidebar dapat dilipat pada tablet
  var t = document.getElementById('menuToggle');
  if (t) t.addEventListener('click', function () { document.body.classList.toggle('menu-open'); });
  document.addEventListener('click', function (e) {
    if (document.body.classList.contains('menu-open') && !e.target.closest('.sidebar') && !e.target.closest('#menuToggle')) {
      document.body.classList.remove('menu-open');
    }
  });

  // Konfirmasi pada aksi penting: <form data-confirm="Yakin?">
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  // Form pesan kamar: durasi aktif hanya untuk sewa sementara (BR-11)
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

  // Tanggal kunjungan tidak boleh melewati tanggal masuk (BR-03)
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
