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

        $output = "-- Database Backup\n";
        $output .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n\n";

        foreach ($tables as $table) {

            $tableName = $table->$key;

            // structure
            $create = DB::select("SHOW CREATE TABLE `$tableName`")[0];
            $output .= "\n\n-- Table: $tableName\n";
            $output .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $output .= $create->{'Create Table'} . ";\n\n";

            // data
            $rows = DB::table($tableName)->get();

            foreach ($rows as $row) {
                $values = [];

                foreach ($row as $value) {
                    if (is_null($value)) {
                        $values[] = "NULL";
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }

                $output .= "INSERT INTO `$tableName` VALUES (" . implode(',', $values) . ");\n";
            }
        }

        return response($output)
            ->header('Content-Type', 'application/sql')
            ->header('Content-Disposition', "attachment; filename=\"$filename\"");
    }
}