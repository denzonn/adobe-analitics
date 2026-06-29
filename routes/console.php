<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduler
|--------------------------------------------------------------------------
|
| Daftarkan scheduled task di sini (Laravel 11+ pakai pendekatan file
| ini, bukan Kernel.php). Untuk benar-benar berjalan, OS tetap perlu
| satu crontab entry yang memanggil `schedule:run` setiap menit —
| lihat README bagian "Cronjob sync Gmail".
|
*/

Schedule::command('emails:sync')
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->runInBackground()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/emails-sync.log'));