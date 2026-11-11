<?php
/** @var string $title */
/** @var string $content */
$user = Auth::user();
$role = $user['peran'] ?? null;
$menu = menu_for($role);
$path = '/' . trim((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'), '/');
if (str_starts_with($path, '/pesan/')) {
    $path = '/kamar';
}
$notifCount = $user ? (new NotifikasiService())->belumDibaca((int)$user['id_pengguna']) : 0;
$aktif = static fn(string $href): bool => $path === $href || str_starts_with($path, $href . '/');
$peranLabel = ['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'][$role] ?? '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> - AmbatuKos</title>
  <link rel="stylesheet" href="/assets/css/app.css?v=1">
</head>
<body class="<?= $user ? 'has-shell' : 'guest' ?>">
<header class="topbar">
  <?php if ($user): ?><button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu">&#9776;</button><?php endif; ?>
  <a class="brand" href="/">AmbatuKos</a>
  <?php if ($user): ?>
    <div class="top-user">
      <a class="bell" href="/notifikasi" title="Notifikasi">Notifikasi<?php if ($notifCount > 0): ?> <span class="dot"><?= (int)$notifCount ?></span><?php endif; ?></a>
      <span class="who"><?= e($user['nama']) ?> <small>(<?= e($peranLabel) ?>)</small></span>
      <form method="post" action="/logout"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Keluar</button></form>
    </div>
  <?php else: ?>
    <nav class="top-links">
      <a href="/kamar">Daftar Kamar</a>
      <a href="/daftar">Daftar</a>
      <a class="btn btn-light btn-sm" href="/login">Masuk</a>
    </nav>
  <?php endif; ?>
</header>

<?php if ($user): ?>
  <aside class="sidebar" id="sidebar">
    <nav aria-label="Menu utama">
      <?php foreach ($menu as [$label, $href]): ?>
        <a href="<?= e($href) ?>" class="<?= $aktif($href) ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
  </aside>
  <nav class="bottombar" aria-label="Menu ponsel">
    <?php foreach ($menu as [$label, $href]): ?>
      <a href="<?= e($href) ?>" class="<?= $aktif($href) ? 'active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
<?php endif; ?>

<main class="content">
  <?php foreach (pull_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<script src="/assets/js/app.js?v=1"></script>
</body>
</html>
