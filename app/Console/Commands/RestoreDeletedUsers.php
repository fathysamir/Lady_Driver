<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreDeletedUsers extends Command
{
    protected $signature   = 'users:restore-deleted';
    protected $description = 'Clear deleted_at for users deleted more than 60 days ago';

    public function handle()
    {
        $count = DB::table('users')
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', now()->subDays(60))
            ->update(['deleted_at' => null]);

        $this->info("Restored $count users deleted more than 60 days ago.");
    }
}