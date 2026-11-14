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
$r->get('/auth/google', [GoogleAuthController::class, 'mulai']);
$r->get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
$r->get('/daftar/google', [GoogleAuthController::class, 'formLengkapi']);
$r->post('/daftar/google', [GoogleAuthController::class, 'simpanLengkapi']);
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
$r->get('/admin/kamar', [KamarController::class, 'kelola'], $AO); // admin: hanya lihat
$r->get('/admin/kamar/baru', [KamarController::class, 'formBaru'], $O);
$r->post('/admin/kamar', [KamarController::class, 'simpanBaru'], $O);
$r->get('/admin/kamar/{id}/ubah', [KamarController::class, 'formUbah'], $O);
$r->post('/admin/kamar/{id}', [KamarController::class, 'simpanUbah'], $O);
$r->post('/admin/kamar/{id}/hapus', [KamarController::class, 'hapus'], $O);
$r->post('/admin/kamar/foto/{id}/hapus', [KamarController::class, 'hapusFoto'], $O);
$r->post('/admin/pemesanan/{id}/akhiri', [PemesananController::class, 'akhiri'], $AO);
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
$r->post('/pemilik/kenaikan-sewa/{id}/umumkan', [PengumumanSewaController::class, 'umumkanPemilik'], $O);

// Manajemen pengguna (admin & pemilik)
$r->get('/pengguna', [PenggunaController::class, 'index'], $AO);
$r->post('/pengguna/{id}/hapus', [PenggunaController::class, 'hapus'], $AO);
$r->post('/pengguna/{id}/status', [PenggunaController::class, 'status'], $AO);
$r->get('/pengguna/baru', [PenggunaController::class, 'formBaru'], $AO);
$r->post('/pengguna', [PenggunaController::class, 'simpanBaru'], $AO);
$r->get('/pengguna/{id}/ubah', [PenggunaController::class, 'formUbah'], $AO);
$r->post('/pengguna/{id}/ubah', [PenggunaController::class, 'ubah'], $AO);

// Laporan
$r->get('/laporan', [LaporanController::class, 'index'], $O);
$r->get('/laporan/unduh', [LaporanController::class, 'unduh'], $O);

// Keluhan
$r->get('/keluhan', [KeluhanController::class, 'index'], ['penyewa', 'admin', 'pemilik']);
$r->post('/keluhan', [KeluhanController::class, 'ajukan'], $P);
$r->post('/keluhan/{id}/status', [KeluhanController::class, 'ubahStatus'], $AO);

// Akun saya (semua peran)
$r->get('/akun', [AkunController::class, 'form'], $ALL);
$r->post('/akun/password', [AkunController::class, 'ubahPassword'], $ALL);
$r->post('/akun/atur-password', [AkunController::class, 'aturPassword'], $ALL);
$r->post('/akun/username', [AkunController::class, 'ubahUsername'], $AO);
$r->post('/akun/nama', [AkunController::class, 'ubahNama'], $ALL);
$r->post('/akun/hp', [AkunController::class, 'ubahNoHp'], $ALL);

// Notifikasi
$r->get('/notifikasi', [NotifikasiController::class, 'index'], $ALL);
$r->post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'], $ALL);
$r->post('/notifikasi/baca/{id}', [NotifikasiController::class, 'baca'], $ALL);

// Fasilitas umum (pemilik) & pengaturan denda (admin)
$r->get('/pemilik/fasilitas', [PengaturanController::class, 'fasilitas'], $O);
$r->post('/pemilik/fasilitas', [PengaturanController::class, 'simpanFasilitas'], $O);
$r->get('/admin/denda', [PengaturanController::class, 'denda'], $A);
$r->post('/admin/denda', [PengaturanController::class, 'simpanDenda'], $A);

// Janji bayar
$r->get('/pembayaran/janji/{id}', [JanjiBayarController::class, 'form'], $P);
$r->post('/pembayaran/janji/{id}', [JanjiBayarController::class, 'ajukan'], $P);
$r->post('/admin/janji/{id}/setujui', [JanjiBayarController::class, 'setujui'], $A);
$r->post('/admin/janji/{id}/tolak', [JanjiBayarController::class, 'tolak'], $A);

// Putus sewa sepihak (admin) & blacklist
$r->post('/admin/pemesanan/{id}/putus', [PemesananController::class, 'putus'], $A);
$r->post('/pengguna/{id}/blacklist', [PenggunaController::class, 'blacklist'], $AO);

return $r;
