<?php
declare(strict_types=1);

/**
 * Menyimpan bukti transfer (di luar web root, NFR-02) dan foto kamar (publik).
 * Validasi format berkas dilakukan berdasarkan isi berkas (finfo), bukan ekstensi.
 */
class FileStorageHandler
{
    private const MAKS_BYTE = 2 * 1024 * 1024;

    private const JENIS = [
        'bukti' => ['dir' => '/storage/bukti', 'mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'application/pdf' => 'pdf']],
        'kamar' => ['dir' => '/public/uploads/kamar', 'mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp']],
    ];

    /** @return string path relatif: "bukti/xxx.jpg" atau "kamar/xxx.jpg" */
    public function simpan(array $file, string $jenis): string
    {
        $cfg = self::JENIS[$jenis];
        $err = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new AppException('Berkas belum dipilih.');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new AppException('Berkas gagal diunggah (kode ' . $err . '). Pastikan ukuran maksimal 2 MB.');
        }
        if (($file['size'] ?? 0) > self::MAKS_BYTE) {
            throw new AppException('Ukuran berkas maksimal 2 MB.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($cfg['mime'][$mime])) {
            throw new AppException('Format berkas tidak didukung. Gunakan: ' . implode(', ', array_unique(array_values($cfg['mime']))) . '.');
        }
        $dir = BASE_PATH . $cfg['dir'];
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new AppException('Folder penyimpanan tidak dapat dibuat.');
        }
        $nama = bin2hex(random_bytes(16)) . '.' . $cfg['mime'][$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) {
            throw new AppException('Berkas tidak dapat disimpan.');
        }
        return $jenis . '/' . $nama;
    }

    public function hapus(string $pathRelatif): void
    {
        $abs = $this->pathAbsolut($pathRelatif);
        if ($abs !== null && is_file($abs)) {
            @unlink($abs);
        }
    }

    public function pathAbsolut(string $pathRelatif): ?string
    {
        [$jenis, $nama] = array_pad(explode('/', $pathRelatif, 2), 2, '');
        if (!isset(self::JENIS[$jenis]) || $nama === '' || $nama !== basename($nama)) {
            return null;
        }
        return BASE_PATH . self::JENIS[$jenis]['dir'] . '/' . $nama;
    }

    /** Ubah struktur $_FILES['x'] multi-unggah menjadi daftar berkas tunggal. */
    public static function normalisasi(?array $files): array
    {
        if (!$files || !is_array($files['name'] ?? null)) {
            return $files && ($files['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? [$files] : [];
        }
        $out = [];
        foreach ($files['name'] as $i => $nama) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = ['name' => $nama, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
                      'error' => $files['error'][$i], 'size' => $files['size'][$i]];
        }
        return $out;
    }
}
