<?php
$tipe = old('denda_tipe', $v['denda_tipe']);
$g = (int)old('grace_hari', $v['grace_hari']);
$m = (int)old('menunggak_hari', $v['menunggak_hari']);
$p = (int)old('batas_putus_hari', $v['batas_putus_hari']);
?>
<p class="small"><a href="/admin/belum-bayar">&larr; Penyewa Belum Bayar</a></p>
<div class="page-head"><div><h1>Pengaturan Denda</h1><p class="muted" style="margin:0">Aturan keterlambatan tagihan sewa bulanan. Denda disimpan sebagai item terpisah dan tidak mengubah harga sewa pokok.</p></div></div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h2>Aturan</h2>
    <form method="post" action="/admin/denda" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label>Tipe denda</label>
        <div class="radio-row">
          <label><input type="radio" name="denda_tipe" value="harian" <?= $tipe === 'harian' ? 'checked' : '' ?>> Harian (dengan batas maksimal)</label>
          <label><input type="radio" name="denda_tipe" value="flat" <?= $tipe === 'flat' ? 'checked' : '' ?>> Flat (sekali kena)</label>
        </div>
      </div>
      <div class="field">
        <label for="denda_nominal">Nominal denda <small id="lblNominal"><?= $tipe === 'flat' ? '(sekali)' : '(per hari)' ?></small></label>
        <input type="text" inputmode="numeric" data-rupiah id="denda_nominal" name="denda_nominal" value="<?= e(old('denda_nominal', $v['denda_nominal'])) ?>" required>
      </div>
      <div class="field" id="boxMaks">
        <label for="denda_maks">Batas maksimal total denda</label>
        <input type="text" inputmode="numeric" data-rupiah id="denda_maks" name="denda_maks" value="<?= e(old('denda_maks', $v['denda_maks'])) ?>">
        <div class="hint">Kosongkan atau 0 untuk tanpa batas.</div>
      </div>
      <div class="cols-2">
        <div class="field">
          <label for="grace_hari">Masa tenggang (hari)</label>
          <input type="number" id="grace_hari" name="grace_hari" min="0" max="14" value="<?= $g ?>" required>
        </div>
        <div class="field">
          <label for="menunggak_hari">Status "Menunggak" mulai H+</label>
          <input type="number" id="menunggak_hari" name="menunggak_hari" min="1" max="60" value="<?= $m ?>" required>
        </div>
      </div>
      <div class="field">
        <label for="batas_putus_hari">Putus sewa sepihak tersedia setelah H+</label>
        <input type="number" id="batas_putus_hari" name="batas_putus_hari" min="2" max="180" value="<?= $p ?>" required>
      </div>
      <button class="btn" type="submit">Simpan</button>
    </form>
  </div>

  <div class="card">
    <h2>Alur keterlambatan (sesuai pengaturan saat ini)</h2>
    <ol style="margin:0;padding-left:20px;line-height:1.8">
      <li><strong>H-3 dan H-1:</strong> pengingat, status <em>Menunggu Pembayaran</em>, belum ada denda.</li>
      <li><strong>Hari H:</strong> status <em>Jatuh Tempo Hari Ini</em>.</li>
      <li><strong>H+1 sampai H+<?= $g ?>:</strong> <em>Terlambat (Masa Tenggang)</em>, denda Rp0, pengingat tiap hari.</li>
      <li><strong>Mulai H+<?= $g + 1 ?>:</strong> <em>Terlambat (Denda Berjalan)</em>, denda dihitung otomatis tiap hari oleh penjadwal.</li>
      <li><strong>Mulai H+<?= $m ?>:</strong> <em>Menunggak</em>: tampil "Prioritas Tagih Langsung" di dashboard admin dan fitur keluhan penyewa dikunci.</li>
      <li><strong>Lebih dari H+<?= $p ?>:</strong> tombol <em>Putus Sewa Sepihak</em> muncul (kamar dikosongkan, penyewa masuk blacklist).</li>
    </ol>
    <p class="muted small" style="margin-bottom:0">Janji bayar yang disetujui menjeda denda sampai tanggal janji; jika lewat dan belum lunas, denda aktif kembali.</p>
  </div>
</div>

<script>
(function () {
  var radios = document.querySelectorAll('input[name="denda_tipe"]');
  var maks = document.getElementById('boxMaks'), lbl = document.getElementById('lblNominal');
  function sync() {
    var flat = (document.querySelector('input[name="denda_tipe"]:checked') || {}).value === 'flat';
    maks.style.display = flat ? 'none' : '';
    lbl.textContent = flat ? '(sekali)' : '(per hari)';
  }
  radios.forEach(function (r) { r.addEventListener('change', sync); });
  sync();
})();
</script>
