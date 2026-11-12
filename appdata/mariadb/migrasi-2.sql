-- Migrasi untuk database yang SUDAH berjalan (aman dijalankan berulang).
-- 1) Hapus kolom alamat (data alamat lama ikut terhapus).
ALTER TABLE pengguna DROP COLUMN IF EXISTS alamat;

-- 2) Seragamkan nomor HP ke format +62.
UPDATE pengguna SET no_hp = CONCAT('+62', SUBSTRING(no_hp, 2)) WHERE no_hp LIKE '08%';
UPDATE pengguna SET no_hp = CONCAT('+', no_hp) WHERE no_hp LIKE '62%';
