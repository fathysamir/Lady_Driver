<?php

namespace App\Http\Controllers;

use App\Models\User;

class ExportController extends Controller
{
    public function exportUsersCsv()
    {
        $fileName = 'users_full_export.csv';

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
        ];

        $callback = function () {

            $file = fopen('php://output', 'w');

            // Get first user to extract ALL columns dynamically
            $firstUser = User::first();

            if (!$firstUser) {
                return;
            }

            // All columns from DB
            $columns = array_keys($firstUser->getAttributes());

            // Write header row
            fputcsv($file, $columns);

            // Stream all users in chunks
            User::orderBy('id', 'asc')
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