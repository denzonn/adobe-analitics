<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom opsional `telegram_chat_id` ke tabel users.
     *
     * - nullable: fitur Telegram sepenuhnya opt-in.
     * - digunakan oleh TelegramNotifier sebagai override per-user;
     *   fallback ke TELEGRAM_CHAT_ID global dari .env kalau kosong.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id', 32)
                ->nullable()
                ->after('email')
                ->comment('Chat ID Telegram untuk notifikasi Adobe. Kosongkan untuk pakai env default.');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });
    }
};
