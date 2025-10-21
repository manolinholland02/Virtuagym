<?php
declare(strict_types=1);

namespace App\Utilities;

final class CsvEmitter
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @param string[] $columns Column order to output
     */
    public static function send(string $filename, array $rows, array $columns): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, $columns);

        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $col) {
                $line[] = $row[$col] ?? '';
            }
            fputcsv($out, $line);
        }
        fclose($out);
    }
}
