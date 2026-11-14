<?php
$fuInfo = PengaturanService::get('fasilitas_info');
$fuGrup = PengaturanService::kelompok('fasilitas_umum');
$fuYa = PengaturanService::baris('harga_termasuk');
$fuTidak = PengaturanService::baris('harga_tidak_termasuk');
if ($fuGrup !== [] || $fuYa !== [] || $fuTidak !== []):
?>
<style>
  .fu-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px}
  .fu-grid ul{margin:6px 0 0;padding-left:18px}
  .fu-grid li{margin-bottom:2px}
  .fu-ok li::marker{color:var(--ok)} .fu-no li::marker{color:var(--danger)}
</style>
<section class="card">
  <h2>Fasilitas Umum</h2>
  <?php if ($fuInfo !== ''): ?><p class="muted" style="margin-top:-6px"><?= e($fuInfo) ?></p><?php endif; ?>
  <?php if ($fuGrup !== []): ?>
  <div class="fu-grid">
    <?php foreach ($fuGrup as $g): ?>
      <div>
        <?php if ($g['judul'] !== ''): ?><h3><?= e($g['judul']) ?></h3><?php endif; ?>
        <ul><?php foreach ($g['item'] as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($fuYa !== [] || $fuTidak !== []): ?>
  <div class="fu-grid" style="margin-top:16px">
    <?php if ($fuYa !== []): ?>
      <div class="dp-box"><h3>Termasuk harga sewa</h3><ul class="fu-ok"><?php foreach ($fuYa as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <?php if ($fuTidak !== []): ?>
      <div class="dp-box" style="background:var(--danger-bg)"><h3>Tidak termasuk</h3><ul class="fu-no"><?php foreach ($fuTidak as $it): ?><li><?= e($it) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>
