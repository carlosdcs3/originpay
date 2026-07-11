<?php

namespace App\Services\Finance\Formatting;

class MoneyFormatter
{
    public static function format(float|string $amount, string $currency = null, bool $withHtml = true): string
    {
        $currency = $currency ?? siteCurrency();
        $amountFloat = (float) $amount;
        $formatted = number_format(abs($amountFloat), 2, ',', '.');
        $sign = $amountFloat < 0 ? '-' : '';
        
        $text = "{$sign} {$currency} {$formatted}";

        if (!$withHtml) {
            return $text;
        }

        $class = $amountFloat >= 0 ? 'amount-in' : 'amount-out';
        return "<span class=\"fw-bold {$class}\">{$text}</span>";
    }

    public static function formatAbsolute(float|string $amount, string $currency = null): string
    {
        $currency = $currency ?? siteCurrency();
        $formatted = number_format(abs((float)$amount), 2, ',', '.');
        return "{$currency} {$formatted}";
    }
}
