<?php
$role = Auth::role();
$cur = $status ?? '';
$rows = $d['rows'];
?>
<div class="page-head">
  <div><h1>Daftar Kamar</h1><p class="muted" style="margin:0"><?= (int)$d['total'] ?> kamar<?= $cur !== '' ? ' berstatus ' . e($cur) : '' ?></p></div>
</div>

<?php include BASE_PATH . '/app/views/kamar/_fasilitas_umum.php'; ?>

<div class="filter">
  <?php foreach (['' => 'Semua', 'Tersedia' => 'Tersedia', 'Dipesan' => 'Dipesan', 'Terisi' => 'Terisi'] as $v => $label): ?>
    <a class="<?= $cur === $v ? 'active' : '' ?>" href="/kamar<?= $v !== '' ? '?status=' . urlencode($v) : '' ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($rows === []): ?>
  <div class="card empty">Belum ada data kamar<?= $cur !== '' ? ' dengan status tersebut' : '' ?>.</div>
<?php else: ?>
  <div class="room-grid">
    <?php foreach ($rows as $k):
        $foto = foto_url($k['foto'] ?? null);
        $fas = array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', (string)$k['fasilitas']) ?: [])));
    ?>
      <article class="room-card">
        <div class="room-photo" <?= $foto ? 'style="background-image:url(\'' . e($foto) . '\')"' : '' ?>>
          <?php if (!$foto): ?><?= e($k['nomor_kamar']) ?><?php endif; ?>
          <?= badge($k['status']) ?>
        </div>
        <div class="room-body">
          <h3>Kamar <?= e($k['nomor_kamar']) ?> <small>&middot; <?= e(rtrim(rtrim((string)$k['luas'], '0'), '.')) ?> m&sup2;</small></h3>
          <div class="price"><?= rupiah($k['biaya_per_bulan']) ?> <small>/ bulan</small></div>
          <div>
            <?php foreach (array_slice($fas, 0, 4) as $f): ?><span class="chip"><?= e($f) ?></span><?php endforeach; ?>
            <?php if (count($fas) > 4): ?><span class="chip">+<?= count($fas) - 4 ?></span><?php endif; ?>
          </div>
          <div class="btn-row">
            <a class="btn btn-outline btn-sm" href="/kamar/<?= (int)$k['id_kamar'] ?>">Detail</a>
            <?php if (!in_array($role, ['admin', 'pemilik'], true)): ?>
              <?php if ($k['status'] === 'Tersedia'): ?>
                <a class="btn btn-sm" href="/pesan/<?= (int)$k['id_kamar'] ?>">Pesan</a>
              <?php else: ?>
                <span class="btn btn-sm disabled" aria-disabled="true">Pesan</span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
  <?= pagination($page, $d['pages']) ?>
<?php endif; ?>
