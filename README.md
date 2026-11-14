# AmbatuKos

PHP 8.3 (tanpa framework) + MariaDB 11 + Docker. Dibangun mengikuti dokumen **Analisis (Bab I–IV)** dan **Bab V Perancangan** (use case UC-01…UC-15, BR-01…BR-13, Tabel 5.4–5.14, UI-01…UI-17).

## Menjalankan
```bash
docker compose up -d --build
```
- Aplikasi: `http://172.20.0.1:8079` (port di-bind ke IP gateway network `ambatukos`, subnet `172.20.0.0/16`, sama polanya dengan `johanz-web`). Arahkan reverse proxy/Traefik ke sana, atau ubah `BIND_IP` di `.env` menjadi `127.0.0.1` untuk akses lokal.
- Jika subnet 172.20.0.0/16 bentrok di server Anda, ganti subnet di `docker-compose.yml` dan `BIND_IP`.
- Pengaturan ada di `.env` (ganti password sebelum dipakai sungguhan).

Saat database masih kosong, akun awal dibuat otomatis:

| Peran | Username | Password |
|---|---|---|
| Pemilik Kos | `ambaperrel` | `pemilik123` |
| Admin | `rusdiwijaya` | `admin123` |
| Penyewa (demo) | `andi` | `penyewa123` |

Plus 6 kamar contoh (`SEED_DEMO=false` untuk mematikan). Reset total: `docker compose down` lalu hapus `appdata/mariadb/data/`.

## Struktur (pola `appdata/` seperti johanz-web)
```
docker-compose.yml        x-common-keys, batas cpu/mem, network khusus
appdata/php/              Dockerfile, apache.conf, php.ini, entrypoint.sh
appdata/mariadb/init.sql  skema (semua FK ON DELETE RESTRICT); data/ = volume database (di-gitignore)
appdata/app/              kode aplikasi (di-mount ke /var/www/html)
  public/                 document root (index.php = front controller, assets, uploads/kamar)
  app/Controllers/        lapisan presentasi
  app/Services/           logika bisnis (BR-xx), transaksi
  app/Repositories/       akses data (PDO prepared statement) + FileStorageHandler
  app/Penjadwal.php       proses otomatis UC-11
  app/views/              tampilan responsif
  scheduler/run.php       dijalankan container ambatukos-scheduler
  storage/bukti/          bukti transfer, di luar web root, dilayani via /berkas/bukti/{id} dengan cek peran
```
Jalankan penjadwal manual: `docker compose exec ambatukos-web php scheduler/run.php`