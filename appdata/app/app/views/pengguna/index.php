<?php
$cur = $peran ?? '';
$me = Auth::id();
$label = ['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'];
$warna = ['penyewa' => 'gray', 'admin' => 'info', 'pemilik' => 'ok'];
$mask = static fn(?string $n): string => $n ? str_repeat('*', max(0, strlen($n) - 4)) . substr($n, -4) : '-';
?>
<div class="page-head">
  <div><h1>Pengguna</h1><p class="muted" style="margin:0"><?= (int)$d['total'] ?> pengguna</p></div>
  <form method="get" action="/pengguna" class="btn-row">
    <?php if ($cur !== ''): ?><input type="hidden" name="peran" value="<?= e($cur) ?>"><?php endif; ?>
    <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, username, HP" style="width:240px">
    <button class="btn btn-outline" type="submit">Cari</button>
  </form>
</div>

<div class="filter">
  <?php foreach (['' => 'Semua', 'penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'] as $v => $l): ?>
    <a class="<?= $cur === $v ? 'active' : '' ?>" href="/pengguna?<?= e(http_build_query(array_filter(['peran' => $v, 'q' => $q]))) ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>

<div class="card">
  <?php if ($d['rows'] === []): ?>
    <div class="empty">Tidak ada pengguna<?= $q !== '' ? ' yang cocok dengan pencarian' : '' ?>.</div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table-cards">
      <thead><tr><th>Nama</th><th>Username</th><th>Peran</th><th>No. HP</th><th>NIK</th><th>Terdaftar</th><th>Status</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($d['rows'] as $u): $self = (int)$u['id_pengguna'] === $me; ?>
        <tr>
          <td data-label="Nama"><strong><?= e($u['nama']) ?></strong></td>
          <td data-label="Username"><?= e($u['username']) ?></td>
          <td data-label="Peran"><?= badge($label[$u['peran']] ?? $u['peran'], $warna[$u['peran']] ?? 'gray') ?></td>
          <td data-label="No. HP"><?= e($u['no_hp'] ?: '-') ?></td>
          <td data-label="NIK"><?= e($mask($u['nik'])) ?></td>
          <td data-label="Terdaftar"><?= e(tgl($u['created_at'])) ?></td>
          <td data-label="Status"><?= badge($u['status_akun'] === 'aktif' ? 'Aktif' : 'Nonaktif', $u['status_akun'] === 'aktif' ? 'ok' : 'gray') ?></td>
          <td data-label="Aksi">
            <?php if ($self): ?>
              <span class="muted small">Akun Anda</span>
            <?php else: ?>
              <div class="row-actions">
                <form method="post" action="/pengguna/<?= (int)$u['id_pengguna'] ?>/status">
                  <?= csrf_field() ?>
                  <input type="hidden" name="status" value="<?= $u['status_akun'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                  <button class="btn btn-outline btn-sm" type="submit"><?= $u['status_akun'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
                <form method="post" action="/pengguna/<?= (int)$u['id_pengguna'] ?>/hapus" data-confirm="<?= $u['peran'] === 'penyewa'
                    ? 'Hapus ' . e($u['nama']) . '? Jika sudah punya riwayat transaksi, data pribadinya (nama, NIK, HP, alamat) dihapus dan riwayat disimpan tanpa identitas. Tidak dapat dibatalkan.'
                    : 'Hapus pengguna ' . e($u['nama']) . '? Tindakan ini tidak dapat dibatalkan.' ?>">
                  <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                </form>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= pagination($page, $d['pages']) ?>
  <?php endif; ?>
</div>
