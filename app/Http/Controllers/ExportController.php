<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ZipArchive;

class ExportController extends Controller
{
    // ============================================================
    // 1. EXPORT SINGLE TABLE AS CSV
    // GET /export/table/{tableName}
    // ============================================================
    public function exportTableCsv(string $tableName)
    {
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
            $file    = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $columns = Schema::getColumnListing($tableName);

            if (empty($columns)) {
                fclose($file);
                return;
            }

            fputcsv($file, $columns);

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
        };

        return response()->stream($callback, 200, $headers);
    }

    // ============================================================
    // 2. EXPORT ALL TABLES AS ZIP OF CSVs
    // GET /export/all
    // ============================================================
    public function exportAllTablesZip()
    {
        $allTables   = $this->getAllTables();
        $zipFileName = 'all_tables_export_' . now()->format('Ymd_His') . '.zip';
        $zipPath     = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($allTables as $tableName) {
            $columns = Schema::getColumnListing($tableName);
            if (empty($columns)) continue;

            $csvContent = $this->generateTableCsv($tableName, $columns);
            $zip->addFromString("csv/{$tableName}.csv", $csvContent);
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ============================================================
    // 3. EXPORT MEDIA URLs AS CSV
    // GET /export/media/urls
    // ============================================================
    public function exportMediaUrlsCsv()
    {
        $baseUrl = 'https://api.lady-driver.com';

        $headers = [
            "Content-Type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=media_urls.csv",
        ];

        $callback = function () use ($baseUrl) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'id',
                'attachmentable_type',
                'attachmentable_id',
                'collection_name',
                'path',
                'full_url',
                'file_exists',
            ]);

            DB::table('media')
                ->orderBy('id')
                ->chunk(500, function ($items) use ($file, $baseUrl) {
                    foreach ($items as $item) {
                        $fullUrl   = $baseUrl . $item->path;
                        $localPath = public_path($item->path);
                        $exists    = file_exists($localPath) ? 'YES' : 'NO';

                        fputcsv($file, [
                            $item->id,
                            $item->attachmentable_type,
                            $item->attachmentable_id,
                            $item->collection_name,
                            $item->path,
                            $fullUrl,
                            $exists,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ============================================================
    // 4. EXPORT ALL MEDIA FILES AS ZIP
    // GET /export/media
    // ============================================================
    public function exportAllMediaZip()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $zipFileName = 'all_media_' . now()->format('Ymd_His') . '.zip';
        $zipPath     = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        $missing = [];

        DB::table('media')
            ->orderBy('id')
            ->chunk(500, function ($items) use ($zip, &$missing) {
                foreach ($items as $item) {
                    $localPath = public_path($item->path);

                    if (file_exists($localPath)) {
                        $zipEntry = $item->collection_name . '/' . basename($item->path);
                        $zip->addFile($localPath, $zipEntry);
                    } else {
                        $missing[] = $item->path;
                    }
                }
            });

        if (!empty($missing)) {
            $zip->addFromString('missing_files.txt', implode("\n", $missing));
        }

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ============================================================
    // 5. FULL SERVER BACKUP (DB + CSV + MEDIA + .ENV)
    // GET /export/full-backup
    // ============================================================
    public function fullBackup()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $timestamp   = now()->format('Ymd_His');
        $zipFileName = "full_backup_{$timestamp}.zip";
        $zipPath     = storage_path("app/{$zipFileName}");

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        // --- DATABASE SQL DUMP ---
        $dbSql = $this->generateSqlDump();
        $zip->addFromString("database/database_backup_{$timestamp}.sql", $dbSql);

        // --- ALL TABLES AS CSV ---
        $allTables = $this->getAllTables();
        foreach ($allTables as $tableName) {
            $columns = Schema::getColumnListing($tableName);
            if (empty($columns)) continue;
            $csvContent = $this->generateTableCsv($tableName, $columns);
            $zip->addFromString("csv/{$tableName}.csv", $csvContent);
        }

        // --- ALL MEDIA FILES from public/images ---
        $imagesPath = public_path('images');
        if (is_dir($imagesPath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($imagesPath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace(
                        public_path() . DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname()
                    );
                    $zip->addFile($file->getPathname(), "public/{$relativePath}");
                }
            }
        }

        // --- STORAGE FOLDER ---
        $storagePath = storage_path('app/public');
        if (is_dir($storagePath)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($storagePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $relativePath = str_replace(
                        storage_path('app/public') . DIRECTORY_SEPARATOR,
                        '',
                        $file->getPathname()
                    );
                    $zip->addFile($file->getPathname(), "storage/{$relativePath}");
                }
            }
        }

        // --- .ENV FILE ---
        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $zip->addFile($envPath, 'config/.env');
        }

        // --- MANIFEST ---
        $manifest = $this->generateManifest($allTables, $timestamp);
        $zip->addFromString('MANIFEST.txt', $manifest);

        $zip->close();

        return response()->download($zipPath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    // ============================================================
    // 6. INDEX PAGE — list all export links
    // GET /export
    // ============================================================
    public function exportIndex()
    {
        $tables = $this->getAllTables();

        $tableLinks = array_map(function ($table) {
            $url = url("/export/table/{$table}");
            return "<li><a href='{$url}'>{$table}.csv</a></li>";
        }, $tables);

        $allTablesUrl  = url('/export/all');
        $mediaUrlsCsv  = url('/export/media/urls');
        $mediaZipUrl   = url('/export/media');
        $fullBackupUrl = url('/export/full-backup');
        $listHtml      = implode("\n", $tableLinks);

        $html = <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Database Export</title>
            <style>
                body  { font-family: Arial, sans-serif; padding: 30px; background: #f5f5f5; }
                h1    { color: #333; }
                h2    { color: #555; margin-top: 30px; }
                ul    { line-height: 2; background: white; padding: 20px 40px; border-radius: 8px; }
                .box  { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
                a     { color: #007bff; }
                .big  { font-size: 1.2em; font-weight: bold; display: block; margin: 10px 0; }
            </style>
        </head>
        <body>
            <h1>📦 Lady Driver — Export Center</h1>

            <div class="box">
                <h2>🔥 Full Backup</h2>
                <a class="big" href="{$fullBackupUrl}">⬇️ Download Full Backup (DB + CSV + Images + .env)</a>
            </div>

            <div class="box">
                <h2>🖼️ Media / Images</h2>
                <a class="big" href="{$mediaZipUrl}">⬇️ Download All Images as ZIP</a>
                <a class="big" href="{$mediaUrlsCsv}">📄 Export Media URLs as CSV</a>
            </div>

            <div class="box">
                <h2>🗄️ All Tables</h2>
                <a class="big" href="{$allTablesUrl}">⬇️ Download All Tables as ZIP</a>
            </div>

            <div class="box">
                <h2>📋 Individual Tables</h2>
                <ul>{$listHtml}</ul>
            </div>
        </body>
        </html>
        HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    private function generateSqlDump(): string
    {
        $sql  = "-- Full Database Backup\n";
        $sql .= "-- Generated: " . now() . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
            $createSql    = $createResult[0]->{'Create Table'};

            $sql .= "-- -----------------------------------------------\n";
            $sql .= "-- Table: {$table}\n";
            $sql .= "-- -----------------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $createSql . ";\n\n";

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                $sql .= "-- (no data)\n\n";
                continue;
            }

            $columns = array_keys((array) $rows->first());
            $colList = implode('`, `', $columns);
            $sql    .= "INSERT INTO `{$table}` (`{$colList}`) VALUES\n";

            $rowValues = [];
            foreach ($rows as $row) {
                $values = array_map(function ($val) {
                    if (is_null($val))    return 'NULL';
                    if (is_numeric($val)) return $val;
                    return "'" . addslashes($val) . "'";
                }, (array) $row);
                $rowValues[] = '(' . implode(', ', $values) . ')';
            }

            $sql .= implode(",\n", $rowValues) . ";\n\n";
        }

        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        return $sql;
    }

    private function generateTableCsv(string $tableName, array $columns): string
    {
        $output = fopen('php://temp', 'r+');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, $columns);

        DB::table($tableName)
            ->orderBy($columns[0], 'asc')
            ->chunk(1000, function ($rows) use ($output, $columns) {
                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $line     = [];
                    foreach ($columns as $col) {
                        $line[] = $rowArray[$col] ?? null;
                    }
                    fputcsv($output, $line);
                }
            });

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    private function generateManifest(array $tables, string $timestamp): string
    {
        $lines   = [];
        $lines[] = "========================================";
        $lines[] = " FULL BACKUP MANIFEST";
        $lines[] = " Generated: {$timestamp}";
        $lines[] = "========================================\n";
        $lines[] = "Contents:";
        $lines[] = "  database/database_backup_{$timestamp}.sql";
        $lines[] = "  csv/ (" . count($tables) . " tables)";
        $lines[] = "  public/images/ (all media files)";
        $lines[] = "  storage/ (uploads & processed files)";
        $lines[] = "  config/.env\n";
        $lines[] = "Tables backed up:";

        foreach ($tables as $table) {
            $count   = DB::table($table)->count();
            $lines[] = "  - {$table} ({$count} rows)";
        }

        $lines[] = "\n========================================";
        $lines[] = " To restore on new server:";
        $lines[] = "  1. Copy config/.env to Laravel root";
        $lines[] = "  2. Import database/*.sql to MySQL";
        $lines[] = "  3. Copy public/ files to new public/";
        $lines[] = "  4. Copy storage/ to storage/app/public/";
        $lines[] = "  5. Run: php artisan storage:link";
        $lines[] = "  6. Run: php artisan config:cache";
        $lines[] = "========================================";

        return implode("\n", $lines);
    }

    private function getAllTables(): array
    {
        return array_map(
            fn($t) => array_values((array) $t)[0],
            DB::select('SHOW TABLES')
        );
    }
}