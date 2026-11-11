-- AmbatuKos - Skema Database (MariaDB)
-- Sesuai Bab V: Tabel 5.4 - 5.14. Semua FK memakai ON DELETE RESTRICT (Tabel 5.14).
SET NAMES utf8mb4;
SET time_zone = '+07:00';

-- Tabel 5.5 pengguna
CREATE TABLE pengguna (
  id_pengguna   INT AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  username      VARCHAR(50)  NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  no_hp         VARCHAR(20)  NULL,
  nik           VARCHAR(16)  NULL,
  alamat        VARCHAR(255) NULL,
  peran         ENUM('penyewa','admin','pemilik') NOT NULL DEFAULT 'penyewa',
  status_akun   ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at    DATETIME NOT NULL,
  UNIQUE KEY uq_pengguna_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.6 kamar
CREATE TABLE kamar (
  id_kamar        INT AUTO_INCREMENT PRIMARY KEY,
  id_pemilik      INT NOT NULL,
  nomor_kamar     VARCHAR(10) NOT NULL,
  luas            DECIMAL(5,2) NOT NULL,
  fasilitas       TEXT NULL,
  biaya_per_bulan DECIMAL(12,2) NOT NULL,
  biaya_per_tahun DECIMAL(12,2) NULL,
  jenis_sewa      ENUM('tetap','sementara') NOT NULL DEFAULT 'tetap',
  status          ENUM('Tersedia','Dipesan','Terisi') NOT NULL DEFAULT 'Tersedia',
  UNIQUE KEY uq_kamar_nomor (nomor_kamar),
  KEY idx_kamar_status (status),
  CONSTRAINT fk_kamar_pemilik FOREIGN KEY (id_pemilik) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.7 kamar_foto
CREATE TABLE kamar_foto (
  id_foto   INT AUTO_INCREMENT PRIMARY KEY,
  id_kamar  INT NOT NULL,
  path_file VARCHAR(255) NOT NULL,
  urutan    INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_foto_kamar FOREIGN KEY (id_kamar) REFERENCES kamar (id_kamar) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.8 pemesanan
CREATE TABLE pemesanan (
  id_pemesanan       INT AUTO_INCREMENT PRIMARY KEY,
  id_penyewa         INT NOT NULL,
  id_kamar           INT NOT NULL,
  tanggal_pesan      DATETIME NOT NULL,
  batas_bayar_dp     DATETIME NOT NULL,
  tanggal_kunjungan  DATE NOT NULL,
  tanggal_masuk      DATE NOT NULL,
  jenis_sewa         ENUM('tetap','sementara') NOT NULL,
  durasi_bulan       INT NULL,
  tanggal_mulai_sewa DATE NULL,
  status             ENUM('Menunggu DP','Menunggu Pelunasan','Aktif','Selesai','Dibatalkan','Kedaluwarsa') NOT NULL DEFAULT 'Menunggu DP',
  alasan_batal       VARCHAR(255) NULL,
  KEY idx_pemesanan_status (status),
  CONSTRAINT chk_pemesanan_tanggal CHECK (tanggal_kunjungan <= tanggal_masuk),
  CONSTRAINT fk_pemesanan_penyewa FOREIGN KEY (id_penyewa) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT,
  CONSTRAINT fk_pemesanan_kamar   FOREIGN KEY (id_kamar)   REFERENCES kamar (id_kamar)       ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.9 tagihan
CREATE TABLE tagihan (
  id_tagihan         INT AUTO_INCREMENT PRIMARY KEY,
  id_pemesanan       INT NOT NULL,
  jenis              ENUM('DP','Pelunasan','Sewa Bulanan','Sewa Tahunan') NOT NULL,
  periode_ke         INT NULL,
  jumlah             DECIMAL(12,2) NOT NULL,
  tanggal_terbit     DATETIME NOT NULL,
  jatuh_tempo        DATETIME NOT NULL,
  status             ENUM('Belum Dibayar','Menunggu Verifikasi','Lunas','Terlambat','Dibatalkan') NOT NULL DEFAULT 'Belum Dibayar',
  pengingat_terkirim TINYINT(1) NOT NULL DEFAULT 0,
  -- Penerbitan tidak ganda (Tabel 5.14)
  UNIQUE KEY uq_tagihan_terbit (id_pemesanan, jenis, periode_ke),
  KEY idx_tagihan_status (status),
  KEY idx_tagihan_jatuh_tempo (jatuh_tempo),
  CONSTRAINT fk_tagihan_pemesanan FOREIGN KEY (id_pemesanan) REFERENCES pemesanan (id_pemesanan) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.10 pembayaran
CREATE TABLE pembayaran (
  id_pembayaran      INT AUTO_INCREMENT PRIMARY KEY,
  id_tagihan         INT NOT NULL,
  id_admin           INT NULL,
  tanggal_bayar      DATETIME NOT NULL,
  jumlah             DECIMAL(12,2) NOT NULL,
  bukti_transfer     VARCHAR(255) NOT NULL,
  status             ENUM('Menunggu Verifikasi','Disetujui','Ditolak','Menunggu Pengembalian','Dikembalikan','Hangus') NOT NULL DEFAULT 'Menunggu Verifikasi',
  tanggal_verifikasi DATETIME NULL,
  catatan_admin      VARCHAR(255) NULL,
  KEY idx_pembayaran_status (status),
  CONSTRAINT fk_pembayaran_tagihan FOREIGN KEY (id_tagihan) REFERENCES tagihan (id_tagihan)  ON DELETE RESTRICT,
  CONSTRAINT fk_pembayaran_admin   FOREIGN KEY (id_admin)   REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.11 pengumuman_sewa
CREATE TABLE pengumuman_sewa (
  id_pengumuman   INT AUTO_INCREMENT PRIMARY KEY,
  id_kamar        INT NOT NULL,
  id_pemilik      INT NOT NULL,
  id_admin        INT NULL,
  biaya_baru      DECIMAL(12,2) NOT NULL,
  tanggal_berlaku DATE NOT NULL,
  status          ENUM('Ditetapkan','Diumumkan') NOT NULL DEFAULT 'Ditetapkan',
  CONSTRAINT fk_pengumuman_kamar  FOREIGN KEY (id_kamar)   REFERENCES kamar (id_kamar)       ON DELETE RESTRICT,
  CONSTRAINT fk_pengumuman_pemilik FOREIGN KEY (id_pemilik) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT,
  CONSTRAINT fk_pengumuman_admin  FOREIGN KEY (id_admin)   REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.12 notifikasi
CREATE TABLE notifikasi (
  id_notifikasi INT AUTO_INCREMENT PRIMARY KEY,
  id_pengguna   INT NOT NULL,
  jenis         VARCHAR(30) NOT NULL,
  pesan         VARCHAR(255) NOT NULL,
  tanggal_kirim DATETIME NOT NULL,
  status_baca   TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_notifikasi_pengguna (id_pengguna, status_baca),
  CONSTRAINT fk_notifikasi_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel 5.13 keluhan
CREATE TABLE keluhan (
  id_keluhan    INT AUTO_INCREMENT PRIMARY KEY,
  id_penyewa    INT NOT NULL,
  id_kamar      INT NOT NULL,
  id_admin      INT NULL,
  judul         VARCHAR(100) NOT NULL,
  deskripsi     TEXT NOT NULL,
  status        ENUM('Baru','Diproses','Selesai') NOT NULL DEFAULT 'Baru',
  tanggal_lapor DATETIME NOT NULL,
  CONSTRAINT fk_keluhan_penyewa FOREIGN KEY (id_penyewa) REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT,
  CONSTRAINT fk_keluhan_kamar   FOREIGN KEY (id_kamar)   REFERENCES kamar (id_kamar)       ON DELETE RESTRICT,
  CONSTRAINT fk_keluhan_admin   FOREIGN KEY (id_admin)   REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
