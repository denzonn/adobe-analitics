<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cookie "remember me" bertahan 2 bulan (60 hari) — sehingga user
        // tidak perlu logout / login ulang selama ~2 bulan sekali.
        // Default Laravel adalah 576000 menit (~400 hari). Di sini kita
        // override ke 86400 menit.
        $rememberDuration = (int) config('auth.remember_duration', 60 * 24 * 60);

        Auth::resolved(function ($auth) use ($rememberDuration) {
            $auth->setRememberDuration($rememberDuration);
        });
    }
}
