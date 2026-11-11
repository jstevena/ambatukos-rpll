<?php
declare(strict_types=1);

$r = new Router();
$P = ['penyewa'];
$A = ['admin'];
$O = ['pemilik'];
$AO = ['admin', 'pemilik'];
$ALL = ['*'];

// Publik
$r->get('/', [HomeController::class, 'index']);
$r->get('/login', [AuthController::class, 'formLogin']);
$r->post('/login', [AuthController::class, 'login']);
$r->get('/daftar', [AuthController::class, 'formRegistrasi']);
$r->post('/daftar', [AuthController::class, 'registrasi']);
$r->post('/logout', [AuthController::class, 'logout'], $ALL);
$r->get('/kamar', [KamarController::class, 'index']);
$r->get('/kamar/{id}', [KamarController::class, 'show']);

// Dashboard per peran
$r->get('/dashboard', [HomeController::class, 'dashboard'], $AO);
$r->get('/beranda', [HomeController::class, 'beranda'], $P);

// Penyewa: pemesanan & pembayaran
$r->get('/pesan/{id}', [PemesananController::class, 'form'], $P);
$r->post('/pesan/{id}', [PemesananController::class, 'simpan'], $P);
$r->get('/pemesanan', [PemesananController::class, 'saya'], $P);
$r->get('/pemesanan/{id}', [PemesananController::class, 'show'], $P);
$r->post('/pemesanan/{id}/batal', [PemesananController::class, 'batal'], $P);
$r->get('/pembayaran', [PembayaranController::class, 'index'], $P);
$r->get('/pembayaran/upload/{id}', [PembayaranController::class, 'formUnggah'], $P);
$r->post('/pembayaran/upload/{id}', [PembayaranController::class, 'unggah'], $P);

// Berkas bukti (akses dibatasi per peran)
$r->get('/berkas/bukti/{id}', [PembayaranController::class, 'bukti'], $ALL);

// Admin
$r->get('/admin/kamar', [KamarController::class, 'kelola'], $A);
$r->get('/admin/kamar/baru', [KamarController::class, 'formBaru'], $A);
$r->post('/admin/kamar', [KamarController::class, 'simpanBaru'], $A);
$r->get('/admin/kamar/{id}/ubah', [KamarController::class, 'formUbah'], $A);
$r->post('/admin/kamar/{id}', [KamarController::class, 'simpanUbah'], $A);
$r->post('/admin/kamar/{id}/hapus', [KamarController::class, 'hapus'], $A);
$r->post('/admin/kamar/foto/{id}/hapus', [KamarController::class, 'hapusFoto'], $A);
$r->post('/admin/pemesanan/{id}/akhiri', [PemesananController::class, 'akhiri'], $A);
$r->get('/admin/verifikasi', [PembayaranController::class, 'verifikasi'], $A);
$r->get('/admin/verifikasi/{id}', [PembayaranController::class, 'tinjau'], $A);
$r->post('/admin/verifikasi/{id}/setujui', [PembayaranController::class, 'setujui'], $A);
$r->post('/admin/verifikasi/{id}/tolak', [PembayaranController::class, 'tolak'], $A);
$r->post('/admin/pengembalian/{id}', [PembayaranController::class, 'kembalikanDp'], $A);
$r->get('/admin/belum-bayar', [PembayaranController::class, 'belumBayar'], $A);
$r->get('/admin/pengumuman', [PengumumanSewaController::class, 'admin'], $A);
$r->post('/admin/pengumuman/{id}/umumkan', [PengumumanSewaController::class, 'umumkan'], $A);

// Pemilik
$r->get('/pemilik/kenaikan-sewa', [PengumumanSewaController::class, 'pemilik'], $O);
$r->post('/pemilik/kenaikan-sewa', [PengumumanSewaController::class, 'tetapkan'], $O);

// Laporan
$r->get('/laporan', [LaporanController::class, 'index'], $AO);
$r->get('/laporan/unduh', [LaporanController::class, 'unduh'], $A);

// Keluhan
$r->get('/keluhan', [KeluhanController::class, 'index'], ['penyewa', 'admin']);
$r->post('/keluhan', [KeluhanController::class, 'ajukan'], $P);
$r->post('/keluhan/{id}/status', [KeluhanController::class, 'ubahStatus'], $A);

// Notifikasi
$r->get('/notifikasi', [NotifikasiController::class, 'index'], $ALL);
$r->post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'], $ALL);
$r->post('/notifikasi/baca/{id}', [NotifikasiController::class, 'baca'], $ALL);

return $r;
