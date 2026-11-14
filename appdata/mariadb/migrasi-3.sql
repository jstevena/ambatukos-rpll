-- Migrasi untuk database yang SUDAH berjalan (aman dijalankan berulang).
-- Fasilitas umum, denda keterlambatan, janji bayar, blacklist; semua kamar menjadi sewa tetap.

ALTER TABLE pengguna
  ADD COLUMN IF NOT EXISTS blacklist TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS catatan_blacklist VARCHAR(255) NULL;

ALTER TABLE tagihan ADD COLUMN IF NOT EXISTS pengingat_terakhir DATE NULL;

CREATE TABLE IF NOT EXISTS pengaturan (
  kunci VARCHAR(50) NOT NULL PRIMARY KEY,
  nilai TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tagihan_denda (
  id_denda          INT AUTO_INCREMENT PRIMARY KEY,
  id_tagihan        INT NOT NULL,
  jumlah            DECIMAL(12,2) NOT NULL DEFAULT 0,
  hari              INT NOT NULL DEFAULT 0,
  terakhir_dihitung DATE NOT NULL,
  UNIQUE KEY uq_denda_tagihan (id_tagihan),
  CONSTRAINT fk_denda_tagihan FOREIGN KEY (id_tagihan) REFERENCES tagihan (id_tagihan) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS janji_bayar (
  id_janji       INT AUTO_INCREMENT PRIMARY KEY,
  id_tagihan     INT NOT NULL,
  id_admin       INT NULL,
  tanggal_janji  DATE NOT NULL,
  alasan         VARCHAR(255) NOT NULL,
  status         ENUM('Diajukan','Disetujui','Ditolak') NOT NULL DEFAULT 'Diajukan',
  catatan_admin  VARCHAR(255) NULL,
  tanggal_ajukan DATETIME NOT NULL,
  KEY idx_janji_status (status),
  CONSTRAINT fk_janji_tagihan FOREIGN KEY (id_tagihan) REFERENCES tagihan (id_tagihan)  ON DELETE RESTRICT,
  CONSTRAINT fk_janji_admin   FOREIGN KEY (id_admin)   REFERENCES pengguna (id_pengguna) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semua kamar disewakan sebagai sewa tetap (minimal 1 bulan)
UPDATE kamar SET jenis_sewa = 'tetap';
