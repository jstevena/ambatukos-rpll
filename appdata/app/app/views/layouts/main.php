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
$inisial = $user ? mb_strtoupper(mb_substr($user['nama'], 0, 1)) : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title><?= e($title) ?> - AmbatuKos</title>
  <!-- Anti-flicker: pakai pilihan tersimpan, jika belum ada ikuti tema browser -->
  <script>
    (function () {
      var s = null;
      try { s = localStorage.getItem('theme'); } catch (e) {}
      var t = s || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="/assets/css/app.css?v=3">
</head>
<body class="<?= $user ? 'has-shell' : 'guest' ?>">
<header class="topbar">
  <div class="topbar-inner">
    <?php if ($user): ?>
      <button type="button" class="icon-btn menu-toggle" id="menuToggle" aria-label="Buka menu">
        <svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    <?php endif; ?>
    <a class="brand" href="/">
      <svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="12" fill="none" stroke="var(--accent)" stroke-width="5"/><circle cx="16" cy="16" r="4.5" fill="var(--accent-2)"/></svg>
      <span>AmbatuKos</span>
    </a>

    <?php if ($user): ?>
      <nav class="pillnav" aria-label="Menu utama">
        <?php foreach ($menu as [$label, $href]): ?>
          <a href="<?= e($href) ?>" class="<?= $aktif($href) ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>

    <div class="top-actions">
      <button type="button" class="icon-btn" id="themeToggle" aria-label="Ganti tema terang/gelap" title="Ganti tema">
        <svg class="moon" viewBox="0 0 24 24"><path d="M21 12.8A8.5 8.5 0 1 1 11.2 3a6.7 6.7 0 0 0 9.8 9.8z"/></svg>
        <svg class="sun" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
      </button>
      <?php if ($user): ?>
        <a class="icon-btn" href="/notifikasi" title="Notifikasi" aria-label="Notifikasi">
          <svg viewBox="0 0 24 24"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8M10.3 20a2 2 0 0 0 3.4 0"/></svg>
          <?php if ($notifCount > 0): ?><span class="dot"><?= (int)$notifCount ?></span><?php endif; ?>
        </a>
        <a class="who" href="/akun" title="<?= e($peranLabel) ?> - ubah password" style="color:inherit;text-decoration:none">
          <span class="avatar"><?= e($inisial) ?></span>
          <b><?= e($user['nama']) ?></b>
        </a>
        <form method="post" action="/logout">
          <?= csrf_field() ?>
          <button class="icon-btn" type="submit" aria-label="Keluar" title="Keluar">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          </button>
        </form>
      <?php else: ?>
        <nav class="top-links">
          <a href="/kamar">Daftar Kamar</a>
          <a href="/daftar">Daftar</a>
          <a class="btn btn-sm" href="/login">Masuk</a>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</header>

<?php if ($user): ?>
  <aside class="sidebar" id="sidebar">
    <nav aria-label="Menu tablet">
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
<script src="/assets/js/app.js?v=3"></script>
</body>
</html>
