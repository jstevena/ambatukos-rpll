-- Migrasi: kolom penanda apakah pengguna sudah mengatur password sendiri.
-- Default 1 (sudah diatur). Pendaftaran via Google akan set ke 0.
ALTER TABLE pengguna
  ADD COLUMN IF NOT EXISTS password_diatur TINYINT(1) NOT NULL DEFAULT 1;
