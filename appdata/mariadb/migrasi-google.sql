-- Migrasi untuk database yang SUDAH berjalan (aman dijalankan berulang).
ALTER TABLE pengguna
  ADD COLUMN IF NOT EXISTS email VARCHAR(100) NULL AFTER username,
  ADD COLUMN IF NOT EXISTS google_id VARCHAR(40) NULL AFTER email,
  ADD UNIQUE INDEX IF NOT EXISTS uq_pengguna_email (email),
  ADD UNIQUE INDEX IF NOT EXISTS uq_pengguna_google (google_id);
