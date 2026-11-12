<?php
$cur = $peran ?? '';
$me = Auth::id();
$myRole = Auth::role();
$label = ['penyewa' => 'Penyewa', 'admin' => 'Admin', 'pemilik' => 'Pemilik Kos'];
$warna = ['penyewa' => 'gray', 'admin' => 'info', 'pemilik' => 'ok'];
?>
<div class="page-head">
  <div><h1>Pengguna</h1><p class="muted" style="margin:0"><?= (int)$d['total'] ?> pengguna</p></div>
  <div class="btn-row">
    <form method="get" action="/pengguna" class="btn-row">
      <?php if ($cur !== ''): ?><input type="hidden" name="peran" value="<?= e($cur) ?>"><?php endif; ?>
      <input type="text" name="q" value="<?= e($q) ?>" placeholder="Cari nama, username, HP" style="width:220px">
      <button class="btn btn-outline" type="submit">Cari</button>
    </form>
    <a class="btn" href="/pengguna/baru">+ Tambah Pengguna</a>
  </div>
</div>

<div class="filter">
  <?php foreach (['' => 'Semua', 'pemilik' => 'Pemilik Kos', 'admin' => 'Admin', 'penyewa' => 'Penyewa', 'mantan' => 'Mantan Penyewa'] as $v => $l): ?>
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
      <?php foreach ($d['rows'] as $u):
          $self = (int)$u['id_pengguna'] === $me;
          $mantan = (int)$u['mantan'] === 1;
          $anonim = str_starts_with((string)$u['username'], 'terhapus_');
          $terkunci = $myRole === 'admin' && $u['peran'] === 'pemilik'; // admin tidak boleh mengelola pemilik
      ?>
        <tr>
          <td data-label="Nama"><strong><?= e($u['nama']) ?></strong></td>
          <td data-label="Username"><?= e($u['username']) ?></td>
          <td data-label="Peran"><?= $mantan ? badge('Mantan Penyewa', 'gray') : badge($label[$u['peran']] ?? $u['peran'], $warna[$u['peran']] ?? 'gray') ?></td>
          <td data-label="No. HP"><?= e(tampil_hp($u['no_hp'])) ?></td>
          <td data-label="NIK"><?= e($u['nik'] ?: '-') ?></td>
          <td data-label="Terdaftar"><?= e(tgl($u['created_at'])) ?></td>
          <td data-label="Status"><?= badge($u['status_akun'] === 'aktif' ? 'Aktif' : 'Nonaktif', $u['status_akun'] === 'aktif' ? 'ok' : 'gray') ?></td>
          <td data-label="Aksi">
            <?php if ($self): ?>
              <span class="muted small">Akun Anda</span>
            <?php elseif ($terkunci): ?>
              <span class="muted small">Hanya dapat dikelola pemilik</span>
            <?php elseif ($anonim): ?>
              <span class="muted small">Data pribadi sudah dihapus</span>
            <?php else: ?>
              <div class="row-actions">
                <?php if ($u['peran'] === 'penyewa' || ($u['peran'] === 'admin' && $myRole === 'pemilik')): ?>
                  <a class="btn btn-outline btn-sm" href="/pengguna/<?= (int)$u['id_pengguna'] ?>/ubah">Ubah</a>
                <?php endif; ?>
                <form method="post" action="/pengguna/<?= (int)$u['id_pengguna'] ?>/status">
                  <?= csrf_field() ?>
                  <input type="hidden" name="status" value="<?= $u['status_akun'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                  <button class="btn btn-outline btn-sm" type="submit"><?= $u['status_akun'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                </form>
                <form method="post" action="/pengguna/<?= (int)$u['id_pengguna'] ?>/hapus" data-confirm="<?= $u['peran'] === 'penyewa'
                    ? 'Hapus ' . e($u['nama']) . '? Jika sudah punya riwayat transaksi, data pribadinya (nama, NIK, HP) dihapus dan riwayat disimpan tanpa identitas. Tidak dapat dibatalkan.'
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
