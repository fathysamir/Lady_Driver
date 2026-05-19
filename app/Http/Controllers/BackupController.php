<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function download()
    {
        $filename = 'db-backup-' . date('Y-m-d_H-i-s') . '.sql';

        return response()->streamDownload(function () {

            $dbName = env('DB_DATABASE');

            // ✅ get ALL tables correctly
            $tables = DB::select("
                SELECT table_name
                FROM information_schema.tables
                WHERE table_schema = ?
            ", [$dbName]);

            echo "-- Database Backup\n";
            echo "-- Date: " . date('Y-m-d H:i:s') . "\n\n\n";

            foreach ($tables as $table) {

                $tableName = $table->table_name;

                echo "\n\n-- Table: $tableName\n";

                // DROP + CREATE
                $create = DB::select("SHOW CREATE TABLE `$tableName`")[0];
                echo "DROP TABLE IF EXISTS `$tableName`;\n";
                echo $create->{'Create Table'} . ";\n\n";

                // DATA (safe chunking)
                DB::table($tableName)->orderByRaw('1')->chunk(500, function ($rows) use ($tableName) {

                    foreach ($rows as $row) {

                        $values = [];

                        foreach ($row as $value) {
                            if (is_null($value)) {
                                $values[] = "NULL";
                            } else {
                                $values[] = "'" . addslashes($value) . "'";
                            }
                        }

                        echo "INSERT INTO `$tableName` VALUES (" . implode(',', $values) . ");\n";
                    }
                });

                echo "\n\n";
            }

        }, $filename, [
            "Content-Type" => "application/sql",
        ]);
    }
}