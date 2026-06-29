<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk notifikasi Telegram ketika ada submission baru /
    | earnings dari Adobe Stock.
    |
    |   TELEGRAM_BOT_TOKEN : token dari @BotFather (format: 123456:ABC...).
    |   TELEGRAM_CHAT_ID   : chat_id default (global). Bisa di-override
    |                        per-user via kolom telegram_chat_id di tabel
    |                        users (lihat migration add_telegram_chat_id
    |                        _to_users_table).
    |
    | Untuk mendapat chat_id, kirim pesan apa saja ke bot lalu buka:
    |   https://api.telegram.org/bot<TOKEN>/getUpdates
    | (atau pakai bot @userinfobot / @RawDataBot).
    |
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id'   => env('TELEGRAM_CHAT_ID'),
    ],
];
