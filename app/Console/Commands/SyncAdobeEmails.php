<?php

namespace App\Console\Commands;

use App\Models\GmailAccount;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Console\Command;
use Throwable;

class SyncAdobeEmails extends Command
{
    /**
     * Nama command ini dipanggil dari scheduler / crontab.
     *
     * @var string
     */
    protected $signature = 'emails:sync
        {--user= : Sinkronkan hanya akun milik user_id tertentu (default: semua user)}
        {--quiet-notify : Nonaktifkan notifikasi Telegram untuk run ini (untuk backfill / test)}';

    /**
     * @var string
     */
    protected $description = 'Sinkronkan email Adobe Stock dari semua akun Gmail (untuk scheduler / cron).';

    public function handle(GmailService $gmailService): int
    {
        $query = GmailAccount::query()->whereNotNull('refresh_token');

        $userId = $this->option('user');
        if ($userId !== null) {
            $query->where('user_id', (int) $userId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('Tidak ada akun Gmail yang memiliki refresh token.');

            return self::SUCCESS;
        }

        $this->info("Memulai sinkronisasi untuk {$accounts->count()} akun Gmail...");

        $succeeded = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $gmailService->sync($account, [
                    'notify' => !$this->option('quiet-notify'),
                    'source' => 'cron',
                ]);
                $succeeded++;
            } catch (Throwable $e) {
                $failed++;
                $this->error("Gagal sync {$account->email}: {$e->getMessage()}");
                report($e);
            }
        }

        $this->info("Selesai. Berhasil: {$succeeded}, Gagal: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
