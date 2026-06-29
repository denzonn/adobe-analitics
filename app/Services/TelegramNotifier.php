<?php

namespace App\Services;

use App\Models\Email;
use App\Models\GmailAccount;
use Illuminate\Support\Facades\Log;

/**
 * Kirim pesan ke Telegram Bot API.
 *
 * Tidak memakai package tambahan — call langsung ke api.telegram.org
 * via cURL (extension cURL adalah dependency inti PHP yang nyaris selalu
 * ada di shared hosting). Kalau cURL tidak tersedia, fallback ke
 * file_get_contents().
 */
class TelegramNotifier
{
    /**
     * Kirim pesan teks ke chat yang dikonfigurasi.
     *
     * Mengembalikan true bila Telegram menerima request (200 OK),
     * false bila tidak ada token/chat_id atau HTTP call gagal.
     */
    public function send(string $text, ?string $chatIdOverride = null): bool
    {
        $token = (string) config('services.telegram.bot_token', '');
        $chatId = $chatIdOverride ?: (string) config('services.telegram.chat_id', '');

        if ($token === '' || $chatId === '') {
            // Notifikasi opt-out: env belum diisi, jangan error.
            return false;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        try {
            $response = $this->httpPost($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('TelegramNotifier: HTTP call gagal', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        $ok = is_array($response) && ($response['ok'] ?? false) === true;
        if (!$ok) {
            Log::warning('TelegramNotifier: API mengembalikan non-ok', [
                'response' => $response,
            ]);
        }

        return $ok;
    }

    /**
     * Bangun dan kirim pesan notifikasi ketika email Adobe baru terdeteksi.
     *
     * Hanya untuk tipe email yang relevan:
     *   - submission_update → accepted / pending / rejected
     *   - earnings_report   → jumlah earning
     *
     * Dipanggil dari GmailService::sync() saat wasRecentlyCreated.
     */
    public function notifyNewAdobeEmail(Email $email, GmailAccount $account): bool
    {
        $text = $this->buildMessage($email, $account);

        if ($text === null) {
            // Tipe email tidak perlu notifikasi (mis. email Adobe lain
            // yang lolos filter tapi tidak punya statistik).
            return false;
        }

        // Prioritas: chat_id per-user (jika di-set di tabel users) > global env.
        $userChatId = $account->user?->telegram_chat_id;

        return $this->send($text, $userChatId);
    }

    /**
     * Susun teks pesan HTML sesuai tipe email. Return null untuk tipe
     * yang tidak ingin di-notify-kan.
     */
    private function buildMessage(Email $email, GmailAccount $account): ?string
    {
        $accountLabel = e($account->email);
        $subject = e((string) $email->subject);

        if ($email->isSubmissionUpdate()) {
            $accepted = (int) ($email->accepted_count ?? 0);
            $rejected = (int) ($email->rejected_count ?? 0);
            $pending  = (int) ($email->pending_count ?? 0);

            // Skip kalau tidak ada perubahan status yang值得 di-notify.
            if ($accepted === 0 && $rejected === 0 && $pending === 0) {
                return null;
            }

            $lines = ["<b>📥 Adobe Stock submission</b>  —  <i>{$accountLabel}</i>"];

            if ($accepted > 0) {
                $lines[] = "✅ Diterima: <b>{$accepted}</b>";
            }
            if ($rejected > 0) {
                $lines[] = "❌ Ditolak: <b>{$rejected}</b>";
            }
            if ($pending > 0) {
                $lines[] = "⏳ Pending: <b>{$pending}</b>";
            }

            $lines[] = "<span class=\"tg-spoiler\">{$subject}</span>";

            return implode("\n", $lines);
        }

        if ($email->isEarningsReport()) {
            $amount = (float) ($email->earnings_amount ?? 0);
            if ($amount <= 0) {
                return null;
            }

            $currency = $email->earnings_currency ?: 'USD';
            $formatted = number_format($amount, 2);

            $lines = [
                "<b>💰 Earnings baru</b>  —  <i>{$accountLabel}</i>",
                "Pendapatan: <b>{$currency} {$formatted}</b>",
                "<span class=\"tg-spoiler\">{$subject}</span>",
            ];

            return implode("\n", $lines);
        }

        return null;
    }

    /**
     * HTTP POST JSON ke URL Telegram. cURL kalau ada, fallback ke stream.
     *
     * @return array<string, mixed>|null
     */
    private function httpPost(string $url, array $payload): ?array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $json,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
            ]);
            $body = curl_exec($ch);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                throw new \RuntimeException("cURL error: {$err}");
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode((string) $body, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        }

        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 8,
            ],
        ]);

        $body = @file_get_contents($url, false, $ctx);

        if ($body === false) {
            throw new \RuntimeException('stream HTTP call gagal');
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
