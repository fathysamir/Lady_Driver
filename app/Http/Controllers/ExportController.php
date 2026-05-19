<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ExportController extends Controller
{
    /**
     * Export a single table as CSV
     * GET /export/table/{tableName}
     */
    public function exportTableCsv(string $tableName)
    {
        // ✅ Whitelist check — only allow existing tables
        $allTables = $this->getAllTables();

        if (!in_array($tableName, $allTables)) {
            abort(404, "Table '$tableName' not found.");
        }

        $fileName = $tableName . '_export.csv';

        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $callback = function () use ($tableName) {

            $file = fopen('php://output', 'w');

            // 🔥 Fix Arabic in Excel (BOM)
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Get columns from the table
            $columns = Schema::getColumnListing($tableName);

            if (empty($columns)) {
                fclose($file);
                return;
            }

            // Header row
            fputcsv($file, $columns);

            // Stream rows in chunks
            DB::table($tableName)
                ->orderBy($columns[0], 'asc')
                ->chunk(1000, function ($rows) use ($file, $columns) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $line = [];
                        foreach ($columns as $col) {
                            $line[] = $rowArray[$col] ?? null;
                        }
                        fputcsv($file, $line);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export ALL tables as a ZIP of CSVs
     * GET /export/all
     */
    public function exportAllTablesZip()
    {
        $allTables = $this->getAllTables();

        $zipFileName = 'all_tables_export_' . now()->format('Ymd_His') . '.zip';
        $zipPath     = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($allTables as $tableName) {
            $columns = Schema::getColumnListing($tableName);

            if (empty($columns)) {
                continue;
            }

            // Write each table to a temp CSV file
            $tmpFile = tempnam(sys_get_temp_dir(), $tableName . '_');
            $file    = fopen($tmpFile, 'w');

            // 🔥 Arabic BOM
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header
            fputcsv($file, $columns);

            // Rows
            DB::table($tableName)
                ->orderBy($columns[0], 'asc')
                ->chunk(1000, function ($rows) use ($file, $columns) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $line     = [];
                        foreach ($columns as $col) {
                            $line[] = $rowArray[$col] ?? null;
                        }
                        fputcsv($file, $line);
                    }
                });

            fclose($file);

            // Add to ZIP
            $zip->addFile($tmpFile, $tableName . '.csv');
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Show all available table export links (HTML page)
     * GET /export
     */
    public function exportIndex()
    {
        $tables = $this->getAllTables();

        $links = array_map(function ($table) {
            $url = url("/export/table/{$table}");
            return "<li><a href='{$url}'>{$table}.csv</a></li>";
        }, $tables);

        $zipUrl   = url('/export/all');
        $listHtml = implode("\n", $links);

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Database Export</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 30px; }
                h1   { color: #333; }
                ul   { line-height: 2; }
                .zip { margin-top: 20px; font-weight: bold; font-size: 1.1em; }
            </style>
        </head>
        <body>
            <h1>📦 Database Table Exports</h1>
            <ul>{$listHtml}</ul>
            <div class="zip">
                ⬇️ <a href="{$zipUrl}">Download ALL tables as ZIP</a>
            </div>
        </body>
        </html>
        HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * Helper — get all table names in the current DB
     */
    private function getAllTables(): array
    {
        return array_map(
            fn($t) => array_values((array) $t)[0],
            DB::select('SHOW TABLES')
        );
    }
}