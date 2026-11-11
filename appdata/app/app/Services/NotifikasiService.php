<?php
declare(strict_types=1);

class NotifikasiService
{
    private NotifikasiRepository $repo;
    private PenggunaRepository $pengguna;

    public function __construct()
    {
        $this->repo = new NotifikasiRepository();
        $this->pengguna = new PenggunaRepository();
    }

    public function kirim(int $idPengguna, string $jenis, string $pesan): void
    {
        $this->repo->create($idPengguna, $jenis, $pesan);
    }

    public function kirimKeAdmin(string $jenis, string $pesan): void
    {
        foreach ($this->pengguna->idsByPeran('admin') as $id) {
            $this->repo->create($id, $jenis, $pesan);
        }
    }

    public function daftar(int $idPengguna, int $page, int $per = 15): array
    {
        [$rows, $total] = $this->repo->daftar($idPengguna, $page, $per);
        return ['rows' => $rows, 'total' => $total, 'pages' => (int)max(1, ceil($total / $per))];
    }

    public function terbaru(int $idPengguna, int $n = 4): array
    {
        return $this->repo->terbaru($idPengguna, $n);
    }

    public function belumDibaca(int $idPengguna): int
    {
        return $this->repo->belumDibaca($idPengguna);
    }

    public function tandaiDibaca(int $idPengguna, int $id): void
    {
        $this->repo->tandaiDibaca($idPengguna, $id);
    }

    public function tandaiSemua(int $idPengguna): void
    {
        $this->repo->tandaiSemua($idPengguna);
    }
}
