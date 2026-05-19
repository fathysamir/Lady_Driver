<?php

namespace App\Http\Controllers;

use App\Models\User;

class ExportController extends Controller
{
    public function exportUsersCsv()
    {
        $fileName = 'users_export.csv';

        $headers = [
            "Content-Type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $callback = function () {

            $file = fopen('php://output', 'w');

            // 🔥 Fix Arabic in Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // 🔥 include soft deleted users
            $firstUser = User::withTrashed()->first();

            if (!$firstUser) {
                return;
            }

            // all columns dynamically
            $columns = array_keys($firstUser->getAttributes());

            // header row
            fputcsv($file, $columns);

            // stream data safely
            User::withTrashed()
                ->orderBy('id', 'asc')
                ->chunk(1000, function ($users) use ($file, $columns) {

                    foreach ($users as $user) {

                        $row = [];

                        foreach ($columns as $col) {
                            $row[] = $user->$col;
                        }

                        fputcsv($file, $row);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}