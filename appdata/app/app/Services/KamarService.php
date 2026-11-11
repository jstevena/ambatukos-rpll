<?php
declare(strict_types=1);

class KamarService
{
    public const PER_HALAMAN = 9;

    private KamarRepository $repo;
    private PemesananRepository $pemesanan;
    private PengumumanSewaRepository $pengumuman;
    private FileStorageHandler $file;

    public function __construct()
    {
        $this->repo = new KamarRepository();
        $this->pemesanan = new PemesananRepository();
        $this->pengumuman = new PengumumanSewaRepository();
        $this->file = new FileStorageHandler();
    }

    public function daftar(?string $status, int $page): array
    {
        if (!in_array($status, ['Tersedia', 'Dipesan', 'Terisi'], true)) {
            $status = null;
        }
        [$rows, $total] = $this->repo->paginate($status, $page, self::PER_HALAMAN);
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)max(1, ceil($total / self::PER_HALAMAN))];
    }

    /** Kamar selalu dimiliki PemilikKos (Tabel 5.6); admin menambah kamar atas nama pemilik. */
    public function pemilikId(): int
    {
        $ids = (new PenggunaRepository())->idsByPeran('pemilik');
        if ($ids === []) {
            throw new AppException('Akun pemilik kos belum tersedia.');
        }
        return $ids[0];
    }

    public function semua(): array
    {
        return $this->repo->all();
    }

    public function detail(int $id): ?array
    {
        $k = $this->repo->find($id);
        if ($k === null) {
            return null;
        }
        $k['fotos'] = $this->repo->fotos($id);
        $k['biaya_sekarang'] = $this->biayaEfektif($k, date('Y-m-d'));
        $k['kenaikan'] = $this->pengumuman->mendatang($id, date('Y-m-d'));
        return $k;
    }

    public function ringkasStatus(): array
    {
        return $this->repo->countByStatus();
    }

    public function penyewaAktifPerKamar(): array
    {
        return $this->pemesanan->aktifIdPerKamar();
    }

    /** Biaya sewa bulanan yang berlaku pada tanggal tertentu (BR-12: kenaikan hanya setelah diumumkan). */
    public function biayaEfektif(array $kamar, string $tanggal): float
    {
        $baru = $this->pengumuman->biayaBerlaku((int)$kamar['id_kamar'], $tanggal);
        return $baru ?? (float)$kamar['biaya_per_bulan'];
    }

    /** UC-08 tambah/ubah kamar. */
    public function simpan(?int $id, int $idPemilik, array $in, array $fotos): int
    {
        $nomor = trim((string)($in['nomor_kamar'] ?? ''));
        $luas = str_replace(',', '.', trim((string)($in['luas'] ?? '')));
        $fasilitas = trim((string)($in['fasilitas'] ?? ''));
        $bulan = (string)($in['biaya_per_bulan'] ?? '');
        $tahun = trim((string)($in['biaya_per_tahun'] ?? ''));
        $jenis = (string)($in['jenis_sewa'] ?? '');

        if ($nomor === '' || $luas === '' || $bulan === '' || $jenis === '') {
            throw new AppException('Nomor kamar, luas, biaya per bulan, dan jenis sewa wajib diisi.');
        }
        if (mb_strlen($nomor) > 10) {
            throw new AppException('Nomor kamar maksimal 10 karakter.');
        }
        if (!is_numeric($luas) || (float)$luas <= 0 || (float)$luas > 999.99) {
            throw new AppException('Luas kamar tidak valid.');
        }
        if (!ctype_digit($bulan) || (float)$bulan <= 0) {
            throw new AppException('Biaya per bulan harus berupa angka lebih dari 0.');
        }
        if ($tahun !== '' && (!ctype_digit($tahun) || (float)$tahun <= 0)) {
            throw new AppException('Biaya per tahun harus berupa angka lebih dari 0.');
        }
        if (!in_array($jenis, ['tetap', 'sementara'], true)) {
            throw new AppException('Jenis sewa tidak valid.');
        }
        $ada = $this->repo->byNomor($nomor);
        if ($ada !== null && (int)$ada['id_kamar'] !== (int)$id) {
            throw new AppException('Nomor kamar sudah ada.');
        }
        $data = [
            'nomor_kamar' => $nomor, 'luas' => $luas, 'fasilitas' => $fasilitas,
            'biaya_per_bulan' => $bulan, 'biaya_per_tahun' => $tahun !== '' ? $tahun : null, 'jenis_sewa' => $jenis,
        ];
        // Simpan berkas dulu agar galat format menggagalkan seluruh proses sebelum menyentuh DB.
        $tersimpan = [];
        try {
            foreach ($fotos as $f) {
                $tersimpan[] = $this->file->simpan($f, 'kamar');
            }
            return Database::transaction(function () use ($id, $idPemilik, $data, $tersimpan) {
                if ($id === null) {
                    $id = $this->repo->create($data + ['id_pemilik' => $idPemilik, 'status' => 'Tersedia']);
                } else {
                    if ($this->repo->find($id) === null) {
                        throw new AppException('Kamar tidak ditemukan.');
                    }
                    $this->repo->update($id, $data);
                }
                $urutan = count($this->repo->fotos($id));
                foreach ($tersimpan as $path) {
                    $this->repo->addFoto($id, $path, ++$urutan);
                }
                return $id;
            });
        } catch (Throwable $e) {
            foreach ($tersimpan as $p) {
                $this->file->hapus($p);
            }
            throw $e;
        }
    }

    public function hapusFoto(int $idFoto): int
    {
        $foto = $this->repo->foto($idFoto);
        if ($foto === null) {
            throw new AppException('Foto tidak ditemukan.');
        }
        $this->repo->deleteFoto($idFoto);
        $this->file->hapus($foto['path_file']);
        return (int)$foto['id_kamar'];
    }

    /** UC-08 alternative flow: kamar yang sedang berjalan pemesanannya tidak dapat dihapus. */
    public function hapus(int $id): void
    {
        $k = $this->repo->find($id);
        if ($k === null) {
            throw new AppException('Kamar tidak ditemukan.');
        }
        if ($k['status'] !== 'Tersedia' || $this->pemesanan->berjalanByKamar($id) !== null) {
            throw new AppException('Kamar yang sedang dipesan atau ditempati tidak dapat dihapus.');
        }
        $fotos = $this->repo->fotos($id);
        try {
            Database::transaction(function () use ($id) {
                $this->repo->deleteFotosByKamar($id);
                $this->repo->delete($id);
            });
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new AppException('Kamar memiliki riwayat pemesanan/pengumuman/keluhan sehingga tidak dapat dihapus.');
            }
            throw $e;
        }
        foreach ($fotos as $f) {
            $this->file->hapus($f['path_file']);
        }
    }
}
