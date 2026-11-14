<?php
declare(strict_types=1);

class PengaturanController extends Controller
{
    /** Fasilitas umum: hanya pemilik. */
    public function fasilitas(): void
    {
        $this->view('pengaturan/fasilitas', ['title' => 'Fasilitas Umum', 'v' => PengaturanService::semua()]);
    }

    public function simpanFasilitas(): void
    {
        PengaturanService::simpanFasilitas($_POST);
        flash('success', 'Fasilitas umum diperbarui. Tampil di halaman Daftar Kamar.');
        $this->redirect('/pemilik/fasilitas');
    }

    /** Aturan denda dan masa tenggang: admin. */
    public function denda(): void
    {
        $this->view('pengaturan/denda', ['title' => 'Pengaturan Denda', 'v' => PengaturanService::semua()]);
    }

    public function simpanDenda(): void
    {
        PengaturanService::simpanDenda($_POST);
        flash('success', 'Pengaturan denda disimpan. Berlaku untuk perhitungan berikutnya.');
        $this->redirect('/admin/denda');
    }
}
