<?php
declare(strict_types=1);

/**
 * Penjadwal (UC-11): berjalan terpisah dari permintaan web (NFR-03).
 * Kegagalan satu langkah tidak menghentikan langkah lain.
 */
class Penjadwal
{
    public function jalankan(): array
    {
        $pemesanan = new PemesananService();
        $tagihan = new TagihanService();
        $langkah = [
            'Tutup pemesanan DP kedaluwarsa (24 jam)' => fn() => $pemesanan->kedaluwarsakanDp(),
            'Tutup pemesanan tanpa pelunasan (DP hangus)' => fn() => $pemesanan->kedaluwarsakanPelunasan(),
            'Akhiri sewa sementara yang habis masa' => fn() => $pemesanan->selesaikanSewaSementara(),
            'Terbitkan tagihan periodik (H-7)' => fn() => $tagihan->terbitkanPeriodik(),
            'Kirim pengingat jatuh tempo (H-3, H-1, hari H)' => fn() => $tagihan->kirimPengingat(),
            'Tandai tagihan Terlambat' => fn() => $tagihan->tandaiTerlambat(),
            'Hitung denda harian' => fn() => $tagihan->hitungDenda(),
            'Kirim pengingat keterlambatan harian' => fn() => $tagihan->kirimPengingatTerlambat(),
        ];
        $log = [];
        foreach ($langkah as $nama => $fn) {
            try {
                $log[] = sprintf('%s: %d', $nama, $fn());
            } catch (Throwable $e) {
                $log[] = sprintf('%s: GAGAL (%s)', $nama, $e->getMessage());
                error_log('[Penjadwal] ' . $nama . ' - ' . $e);
            }
        }
        return $log;
    }
}
