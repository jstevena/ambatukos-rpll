<?php
$hariIni = date('Y-m-d');
?>
<p class="small"><a href="/kamar/<?= (int)$k['id_kamar'] ?>">&larr; Detail kamar</a></p>
<div class="page-head"><h1>Pesan Kamar <?= e($k['nomor_kamar']) ?></h1></div>

<div class="grid grid-2" style="align-items:start">
  <div class="card">
    <h2>Ringkasan Kamar</h2>
    <dl class="kv">
      <dt>Luas</dt><dd><?= e(rtrim(rtrim((string)$k['luas'], '0'), '.')) ?> m&sup2;</dd>
      <dt>Fasilitas</dt><dd><?= e($k['fasilitas']) ?></dd>
      <dt>Biaya per bulan</dt><dd><strong><?= rupiah($k['biaya_sekarang']) ?></strong></dd>
    </dl>
    <div class="dp-box" style="margin-top:14px">
      <div class="small muted">DP <?= e(rtrim(rtrim(number_format($dpPersen, 2, '.', ''), '0'), '.')) ?>% &middot; dibayar sekarang</div>
      <div class="big"><?= rupiah($est['dp']) ?></div>
      <div class="small">Batas bayar <strong>24 jam</strong> setelah pemesanan. Sisa pelunasan <?= rupiah($est['pelunasan']) ?> dibayar paling lambat pada tanggal masuk, setelah Anda melihat kamar.</div>
    </div>
  </div>

  <div class="card">
    <h2>Data Pemesanan</h2>
    <form method="post" action="/pesan/<?= (int)$k['id_kamar'] ?>">
      <?= csrf_field() ?>
      <div class="field">
        <label for="tanggal_kunjungan">Tanggal kunjungan</label>
        <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" min="<?= $hariIni ?>" value="<?= e(old('tanggal_kunjungan')) ?>" required>
        <div class="hint">Tanggal untuk melihat kamar secara langsung. Harus sebelum atau sama dengan tanggal masuk.</div>
      </div>
      <div class="field">
        <label for="tanggal_masuk">Tanggal masuk</label>
        <input type="date" id="tanggal_masuk" name="tanggal_masuk" min="<?= $hariIni ?>" value="<?= e(old('tanggal_masuk')) ?>" required>
        <div class="hint">Menjadi tanggal mulai sewa dan batas pelunasan.</div>
      </div>
      <div class="alert alert-info" style="margin-bottom:16px">Sewa minimal <strong>1 bulan</strong> dan berlanjut setiap bulan sampai Anda berhenti. Tagihan bulan berikutnya terbit otomatis 7 hari sebelum jatuh tempo.</div>
      <div class="btn-row">
        <button class="btn" type="submit">Pesan &amp; Buat Tagihan DP</button>
        <a class="btn btn-outline" href="/kamar/<?= (int)$k['id_kamar'] ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
