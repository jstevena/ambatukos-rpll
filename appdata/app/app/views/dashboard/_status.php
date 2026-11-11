<?php
$warna = ['Tersedia' => 'var(--ok)', 'Dipesan' => 'var(--warn)', 'Terisi' => 'var(--gray-dot)'];
$tot = max(1, (int)$totalKamar);
?>
<div class="stack" role="img" aria-label="Proporsi status kamar">
  <?php foreach ($warna as $s => $c): if (($status[$s] ?? 0) > 0): ?>
    <span style="width:<?= round($status[$s] / $tot * 100, 2) ?>%;background:<?= $c ?>"></span>
  <?php endif; endforeach; ?>
</div>
<div class="legend">
  <?php foreach ($warna as $s => $c): ?><span><i style="background:<?= $c ?>"></i><?= e($s) ?> (<?= (int)($status[$s] ?? 0) ?>)</span><?php endforeach; ?>
</div>
