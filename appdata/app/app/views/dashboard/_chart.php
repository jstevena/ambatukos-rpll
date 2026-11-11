<?php $maks = max(1.0, ...array_map(static fn($x) => (float)$x['total'], $seri)); ?>
<div class="bars" role="img" aria-label="Grafik pendapatan 6 bulan terakhir">
  <?php foreach ($seri as $x): ?>
    <div class="bar-col">
      <div class="v"><?= $x['total'] > 0 ? e(number_format($x['total'] / 1000, 0, ',', '.') . 'rb') : '0' ?></div>
      <div class="bar" style="height:<?= (int)round($x['total'] / $maks * 100) ?>%"></div>
      <div class="m"><?= e(BULAN_ID[(int)substr($x['bulan'], 5, 2)]) ?></div>
    </div>
  <?php endforeach; ?>
</div>
