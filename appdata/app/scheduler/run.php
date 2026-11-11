<?php
declare(strict_types=1);
/**
 * Penjadwal AmbatuKos.
 *   php scheduler/run.php          -> jalankan sekali
 *   php scheduler/run.php --loop   -> jalan terus tiap SCHEDULER_INTERVAL detik (dipakai container scheduler)
 */
require __DIR__ . '/../app/bootstrap.php';

$loop = in_array('--loop', $argv ?? [], true);
$interval = max(30, (int)env('SCHEDULER_INTERVAL', '300'));

do {
    try {
        Database::reset(); // koneksi baru pada tiap putaran
        $log = (new Penjadwal())->jalankan();
        echo '[' . date('Y-m-d H:i:s') . '] ' . implode(' | ', $log) . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, '[' . date('Y-m-d H:i:s') . '] Penjadwal gagal: ' . $e->getMessage() . PHP_EOL);
    }
    if ($loop) {
        sleep($interval);
    }
} while ($loop);
