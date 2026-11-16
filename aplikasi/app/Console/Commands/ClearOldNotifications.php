<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClearOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus notifikasi yang lebih dari 30 hari dari database';
    /**
     * Execute the console command.
     */

    public function handle()
    {
        $deleted = DB::table('notifications')
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->delete();

        $this->info("Berhasil menghapus $deleted notifikasi lama.");
    }
}
