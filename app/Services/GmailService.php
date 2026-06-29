<?php

namespace App\Services;

use App\Models\Email;
use App\Models\GmailAccount;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;

class GmailService
{
    public function sync(GmailAccount $account)
    {
        $client = new Client();

        $client->setClientId(
            config('services.google.client_id')
        );

        $client->setClientSecret(
            config('services.google.client_secret')
        );

        $client->setAccessToken([
            'access_token' => $account->access_token,
            'refresh_token' => $account->refresh_token,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Refresh Token
        |--------------------------------------------------------------------------
        */

        if (
            $client->isAccessTokenExpired()
            && $account->refresh_token
        ) {

            $newToken = $client->fetchAccessTokenWithRefreshToken(
                $account->refresh_token
            );

            if (!isset($newToken['error'])) {

                $account->update([
                    'access_token' => $newToken['access_token']
                ]);

                $client->setAccessToken([
                    'access_token' => $newToken['access_token'],
                    'refresh_token' => $account->refresh_token,
                ]);
            }
        }

        $gmail = new Gmail($client);

        /*
        |--------------------------------------------------------------------------
        | Ambil semua email dari sender Adobe Stock Contributor.
        | Pakai updateOrCreate by gmail_message_id agar:
        |   1. Re-sync tidak menghapus data lama kalau Gmail sementara error.
        |   2. Tidak ada duplikat walau email yang sama di-fetch ulang.
        |--------------------------------------------------------------------------
        */

        $messages = $gmail->users_messages->listUsersMessages(
            'me',
            [
                'maxResults' => 500,
                'q' => 'from:(stock-contributor@adobe.com OR contributor@stock.adobe.com OR stock@adobe.com OR noreply@stock.adobe.com OR notifications@stock.adobe.com OR contributor-digest@adobe.com OR no-reply@stock.adobe.com)'
            ]
        );

        if (!$messages->getMessages()) {
            return;
        }

        foreach ($messages->getMessages() as $message) {

            $fullMessage = $gmail->users_messages->get(
                'me',
                $message->getId(),
                [
                    'format' => 'full'
                ]
            );

            $headers = collect(
                $fullMessage->getPayload()->getHeaders()
            );

            $subject = optional(
                $headers->firstWhere(
                    'name',
                    'Subject'
                )
            )->value;

            $sender = optional(
                $headers->firstWhere(
                    'name',
                    'From'
                )
            )->value;

            $subjectLower = strtolower(
                $subject ?? ''
            );

            $senderLower = strtolower(
                $sender ?? ''
            );

            /*
            |--------------------------------------------------------------------------
            | Filter Adobe Stock Contributor Emails Only
            |--------------------------------------------------------------------------
            */

            // Sender email patterns for Adobe Stock
            $contributorSenderPatterns = [
                'stock-contributor@',
                'contributor@stock.adobe.com',
                'stock@adobe.com',
                'stock.adobe.com',
                'notifications@stock',
                'contributor-digest@',
                'no-reply@stock.adobe.com',
            ];

            $isContributorSender = false;
            foreach ($contributorSenderPatterns as $pattern) {
                if (str_contains($senderLower, strtolower($pattern))) {
                    $isContributorSender = true;
                    break;
                }
            }

            // Subject/body keywords for Adobe Stock contributor
            $contributorKeywords = [
                // Adobe Stock specific
                'adobe stock',
                'stock contributor',
                'contributor program',
                'stock.adobe.com',

                // Submission related
                'submission',
                'submitted',
                'submit',
                'review',
                'pending review',
                'under review',
                'content review',

                // Status
                'approved',
                'rejected',
                'accepted',
                'declined',

                // Earnings & Payment
                'earnings',
                'royalty',
                'payout',
                'payment',
                'statement',
                'invoice',

                // Sales
                'sale',
                'sales',

                // Contributor features
                'level',
                'milestone',
                'portfolio',
                'image accepted',
                'image rejected',
                'contributor level',
                'new submission',

                // Tax & Forms
                'tax form',
                'tax document',
                '1099',

                // Notifications
                'license sale',
                'new sale',
                'download',
                'royalty report',
                'monthly statement',
            ];

            $isContributorEmail = false;

            // Check if sender is from Adobe Stock
            if ($isContributorSender) {
                $isContributorEmail = true;
            }

            // Also check subject for contributor keywords
            foreach ($contributorKeywords as $keyword) {
                if (
                    str_contains($subjectLower, $keyword)
                    || str_contains($senderLower, $keyword)
                ) {
                    $isContributorEmail = true;
                    break;
                }
            }

            if (!$isContributorEmail) {
                continue;
            }

            $receivedAt = Carbon::createFromTimestamp(
                $fullMessage->getInternalDate() / 1000
            );

            $body = $this->getBody(
                $fullMessage->getPayload()
            );

            // Parse statistik dari body berdasarkan tipe email.
            $parsed = $this->parseEmailBody(
                $subject ?? '',
                $body ?? '',
                $fullMessage->getSnippet() ?? ''
            );

            Email::updateOrCreate(
                [
                    'gmail_account_id' => $account->id,
                    'gmail_message_id' => $message->getId(),
                ],
                [
                    'sender' => $sender,

                    'subject' => $subject,

                    'snippet' => $fullMessage->getSnippet(),

                    'email_type'         => $parsed['email_type'],
                    'accepted_count'     => $parsed['accepted_count'],
                    'pending_count'      => $parsed['pending_count'],
                    'rejected_count'     => $parsed['rejected_count'],
                    'earnings_amount'    => $parsed['earnings_amount'],
                    'earnings_currency'  => $parsed['earnings_currency'],

                    'body' => $body,

                    'received_at' => $receivedAt,
                ]
            );
        }
    }

    /**
     * Parse body email Adobe untuk mengambil statistik submission / earnings.
     *
     * Submission update (subject: "Updates from your Adobe Stock submission")
     *   - Accepted
     *   - Pending Reminders
     *   - Weren't accepted
     *
     * Daily earnings report (subject: "Daily Earnings Report")
     *   - amount (contoh: $0.93)
     */
    private function parseEmailBody(string $subject, string $body, string $snippet): array
    {
        $result = [
            'email_type'        => null,
            'accepted_count'    => null,
            'pending_count'     => null,
            'rejected_count'    => null,
            'earnings_amount'   => null,
            'earnings_currency' => null,
        ];

        $subjectLower = strtolower($subject);
        // Gabungkan body + snippet agar pencarian tidak miss pada email yang
        // ringkas (mis. plain text digest) atau hanya HTML penuh.
        $haystack = strtolower(strip_tags($body . "\n" . $snippet));

        /*
        |--------------------------------------------------------------------------
        | Submission Update
        |--------------------------------------------------------------------------
        | Pola pada email "Updates from your Adobe Stock submission":
        |   SUMMARY
        |   18             0              23
        |   Accepted       Pending Reminders   Weren't accepted
        */

        if (
            str_contains($subjectLower, 'updates from your adobe stock submission')
            || str_contains($haystack, 'updates from your adobe stock submission')
            || (str_contains($haystack, 'accepted')
                && str_contains($haystack, 'pending reminders')
                && str_contains($haystack, "weren't accepted"))
        ) {
            $numbers = $this->extractNumberRow($body, $snippet);

            if ($numbers !== null) {
                $result['email_type']     = Email::TYPE_SUBMISSION_UPDATE;
                $result['accepted_count'] = $numbers[0] ?? null;
                $result['pending_count']  = $numbers[1] ?? null;
                $result['rejected_count'] = $numbers[2] ?? null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Daily Earnings Report
        |--------------------------------------------------------------------------
        | Pola pada email "Daily Earnings Report":
        |   Congratulations, ...!
        |   Yesterday you made.
        |   $0.93
        */

        if (
            str_contains($subjectLower, 'daily earnings report')
            || str_contains($haystack, 'daily earnings report')
            || str_contains($haystack, 'yesterday you made')
        ) {
            $amount = $this->extractEarningsAmount($body, $snippet);

            if ($amount !== null) {
                $result['email_type']        = Email::TYPE_EARNINGS_REPORT;
                $result['earnings_amount']   = $amount['value'];
                $result['earnings_currency'] = $amount['currency'];
            }
        }

        return $result;
    }

    /**
     * Mengambil baris angka di atas label Accepted / Pending Reminders /
     * Weren't accepted dari body email submission update.
     *
     * Adobe mengirim email sebagai tabel: baris angka tepat di atas baris
     * label. Penting: kita pasangkan angka dan label berdasarkan POSISI
     * KOLOM yang sama — bukan "cell terakhir sebelum label", yang akan
     * keliru mengambil angka dari kolom sebelah kanan.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    private function extractNumberRow(string $body, string $snippet): ?array
    {
        $labelKeys = [
            'accepted'         => 0,
            'pending reminders' => 1,
            "weren't accepted" => 2,
        ];

        // 1) Coba parsing body sebagai tabel HTML.
        $tableRows = $this->parseHtmlTableRows($body);

        if ($tableRows !== null) {
            $mapped = $this->mapNumberRowByLabels($tableRows, $labelKeys);
            if ($mapped !== null) {
                return $mapped;
            }
        }

        // 2) Fallback: pakai snippet (text-only). Pada snippet Adobe, urutan
        //    SELALU: 3 angka dulu (Accepted, Pending, Rejected), lalu 3 label.
        //    Jadi kita petakan berdasarkan urutan, bukan "angka sebelum label".
        $numbers = $this->extractLeadingNumberTriple($snippet !== '' ? $snippet : strip_tags($body));
        if ($numbers !== null) {
            return $numbers;
        }

        return null;
    }

    /**
     * Parse body HTML jadi array 2-dimensi [row][col] = innerHTML per <td>.
     *
     * @return array<int, array<int, string>>|null  null bila tidak ada tabel.
     */
    private function parseHtmlTableRows(string $body): ?array
    {
        if ($body === '' || !preg_match_all('/<tr\b[^>]*>(.*?)<\/tr>/is', $body, $trMatches)) {
            return null;
        }

        $rows = [];
        foreach ($trMatches[1] as $trInner) {
            if (preg_match_all('/<td\b[^>]*>(.*?)<\/td>/is', $trInner, $tdMatches)) {
                $rows[] = $tdMatches[1];
            }
        }

        return $rows ?: null;
    }

    /**
     * Cari baris yang berisi SEMUA label (Accepted / Pending Reminders /
     * Weren't accepted), lalu ambil angka dari baris sebelumnya di kolom
     * yang sama untuk tiap label.
     *
     * @param array<int, array<int, string>> $rows
     * @param array<string, int>             $labelKeys  label => index output
     * @return array{0:int,1:int,2:int}|null
     */
    private function mapNumberRowByLabels(array $rows, array $labelKeys): ?array
    {
        foreach ($rows as $i => $row) {
            $labelsByColumn = [];
            foreach ($row as $colIdx => $cell) {
                $cellText = strtolower(trim(strip_tags($cell)));
                if (isset($labelKeys[$cellText])) {
                    $labelsByColumn[$colIdx] = $cellText;
                }
            }

            // Butuh minimal 2 label biar yakin ini baris label Adobe.
            if (count($labelsByColumn) < 2 || $i === 0) {
                continue;
            }

            $numberRow = $rows[$i - 1];
            $result = [0, 0, 0];
            $found = 0;

            foreach ($labelsByColumn as $colIdx => $label) {
                if (!isset($numberRow[$colIdx])) {
                    continue;
                }
                if (preg_match('/(-?\d+(?:[.,]\d+)?)/', strip_tags($numberRow[$colIdx]), $m)) {
                    $result[$labelKeys[$label]] = (int) $m[1];
                    $found++;
                }
            }

            if ($found >= 2) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Fallback text-only: cari 3 angka berurutan di awal snippet/body
     * yang kemudian diikuti label Accepted / Pending Reminders / Weren't
     * accepted. Pemetaan ke kolom mengikuti URUTAN label, karena snippet
     * tidak lagi menyimpan informasi kolom.
     *
     * @return array{0:int,1:int,2:int}|null
     */
    private function extractLeadingNumberTriple(string $text): ?array
    {
        $text = strtolower(strip_tags($text));

        if (
            !str_contains($text, 'accepted')
            || !str_contains($text, 'pending reminders')
            || !str_contains($text, "weren't accepted")
        ) {
            return null;
        }

        if (!preg_match_all('/(-?\d+(?:[.,]\d+)?)/', $text, $m)) {
            return null;
        }

        $nums = array_map(static function ($v) {
            return (int) $v;
        }, $m[1]);

        // Cari run 3 angka sebelum kemunculan pertama label "accepted".
        $acceptedPos = strpos($text, 'accepted');
        if ($acceptedPos === false) {
            return null;
        }

        $before = substr($text, 0, $acceptedPos);
        if (!preg_match_all('/(-?\d+(?:[.,]\d+)?)/', $before, $beforeMatches)) {
            return null;
        }

        $beforeNums = array_map('intval', $beforeMatches[1]);
        if (count($beforeNums) < 3) {
            return null;
        }

        // Ambil 3 angka terakhir sebelum label "accepted".
        $triple = array_slice($beforeNums, -3);

        return [
            $triple[0] ?? 0,
            $triple[1] ?? 0,
            $triple[2] ?? 0,
        ];
    }

    /**
     * Mengambil nilai mata uang + angka dari email Daily Earnings Report.
     *
     * @return array{value: float, currency: string}|null
     */
    private function extractEarningsAmount(string $body, string $snippet): ?array
    {
        $sources = [$body, $snippet];

        foreach ($sources as $html) {
            if ($html === '') {
                continue;
            }

            // Pola: simbol mata uang opsional, angka dengan optional desimal.
            if (preg_match(
                '/(\$|€|£|¥|USD|EUR|GBP|JPY|IDR)\s*([0-9]{1,3}(?:[.,][0-9]{3})*(?:[.,][0-9]{1,4})?|[0-9]+(?:[.,][0-9]{1,4})?)/i',
                strip_tags($html),
                $m
            )) {
                $rawSymbol = strtoupper($m[1]);
                $currency = match ($rawSymbol) {
                    '$'    => 'USD',
                    '€'    => 'EUR',
                    '£'    => 'GBP',
                    '¥'    => 'JPY',
                    default => $rawSymbol,
                };

                $value = (float) str_replace(',', '.', $m[2]);

                return [
                    'value'    => $value,
                    'currency' => $currency,
                ];
            }
        }

        return null;
    }

    private function getBody($payload)
    {
        if ($payload->getBody()->getData()) {

            return base64_decode(
                strtr(
                    $payload->getBody()->getData(),
                    '-_',
                    '+/'
                )
            );
        }

        foreach ($payload->getParts() ?? [] as $part) {

            if (
                $part->getMimeType() === 'text/html'
                && $part->getBody()->getData()
            ) {

                return base64_decode(
                    strtr(
                        $part->getBody()->getData(),
                        '-_',
                        '+/'
                    )
                );
            }

            if ($part->getParts()) {

                foreach ($part->getParts() as $subPart) {

                    if (
                        $subPart->getMimeType() === 'text/html'
                        && $subPart->getBody()->getData()
                    ) {

                        return base64_decode(
                            strtr(
                                $subPart->getBody()->getData(),
                                '-_',
                                '+/'
                            )
                        );
                    }
                }
            }
        }

        return null;
    }
}
