<?php

namespace App\Support;

/**
 * Utilitas format angka & mata uang untuk dashboard Adobe Mail Center.
 */
class AdobeStatsFormatter
{
    /**
     * Format nilai mata uang dengan simbol yang sesuai.
     */
    public static function money(float $amount, string $currency = 'USD'): string
    {
        $symbol = match (strtoupper($currency)) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'IDR' => 'Rp',
            default => $currency . ' ',
        };

        // IDR tanpa desimal; lainya 2 desimal.
        $decimals = strtoupper($currency) === 'IDR' ? 0 : 2;

        return $symbol . number_format($amount, $decimals, ',', '.');
    }

    /**
     * Format jumlah asset sebagai integer dengan separator ribuan.
     */
    public static function int(int $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    /**
     * Format singkat (mis. 1.2K, 3.4M) untuk angka besar.
     */
    public static function short(int $value): string
    {
        if ($value < 1000) {
            return (string) $value;
        }
        if ($value < 1_000_000) {
            return rtrim(rtrim(number_format($value / 1000, 1, ',', '.'), '0'), ',') . 'K';
        }
        return rtrim(rtrim(number_format($value / 1_000_000, 1, ',', '.'), '0'), ',') . 'M';
    }
}