<?php
/** Peringatan keras untuk penyewa yang menunggak (H+8 bawaan). Variabel: $menunggak (tagihan atau null). */
if (!empty($menunggak)):
    $hrMenunggak = hari_telat($menunggak['jatuh_tempo']);
    $totMenunggak = (float)$menunggak['jumlah'] + (float)($menunggak['denda'] ?? 0);
?>
<div class="alert alert-error" role="alert">
  <strong>PERINGATAN:</strong> tagihan sewa kamar <?= e($menunggak['nomor_kamar']) ?> menunggak <strong><?= (int)$hrMenunggak ?> hari</strong>.
  Total yang harus dibayar <strong><?= e(rupiah($totMenunggak)) ?></strong><?= (float)($menunggak['denda'] ?? 0) > 0 ? ' (termasuk denda ' . e(rupiah($menunggak['denda'])) . ')' : '' ?>.
  Fitur keluhan dikunci sampai tagihan lunas. <a href="/pembayaran">Bayar sekarang</a>
</div>
<?php endif; ?>
