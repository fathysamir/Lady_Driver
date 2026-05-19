<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function download()
    {
        $filename = 'db-backup-' . date('Y-m-d_H-i-s') . '.sql';

        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $key = "Tables_in_$dbName";

        return response()->streamDownload(function () use ($tables, $key) {

            $output = "-- Database Backup\n";
            $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n\n";

            foreach ($tables as $table) {

                $tableName = $table->$key;

                // structure
                $create = DB::select("SHOW CREATE TABLE `$tableName`")[0];
                $output .= "\n-- Table: $tableName\n";
                $output .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $output .= $create->{'Create Table'} . ";\n\n";

                echo $output;
                $output = "";

                // DATA (IMPORTANT: chunk instead of get)
                DB::table($tableName)->orderBy('id')->chunk(500, function ($rows) use ($tableName) {

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