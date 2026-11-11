<?php
declare(strict_types=1);

abstract class Repository
{
    protected function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = "INSERT INTO $table (" . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')';
        Database::exec($sql, array_values($data));
        return Database::lastId();
    }

    protected function updateById(string $table, string $pk, int $id, array $data): void
    {
        if ($data === []) {
            return;
        }
        $set = implode(', ', array_map(static fn($c) => "$c = ?", array_keys($data)));
        Database::exec("UPDATE $table SET $set WHERE $pk = ?", [...array_values($data), $id]);
    }
}
