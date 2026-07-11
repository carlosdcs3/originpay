<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PixKeyRule implements ValidationRule
{
    protected $type;

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isValid = match ($this->type) {
            'cpf' => $this->validateCpf($value),
            'cnpj' => $this->validateCnpj($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'phone' => $this->validatePhone($value),
            'random' => $this->validateRandom($value),
            default => false,
        };

        if (!$isValid) {
            $fail("O formato da chave PIX para o tipo '{$this->type}' é inválido.");
        }
    }

    protected function validateCpf($c)
    {
        $c = preg_replace('/\D/', '', $c);
        if (strlen($c) != 11 || preg_match("/^{$c[0]}{11}$/", $c)) return false;
        for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
        if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
        if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        return true;
    }

    protected function validateCnpj($c)
    {
        $c = preg_replace('/\D/', '', $c);
        if (strlen($c) != 14 || preg_match("/^{$c[0]}{14}$/", $c)) return false;
        $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0, $n = 0; $i < 12; $n += $c[$i] * $b[++$i]);
        if ($c[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        for ($i = 0, $n = 0; $i <= 12; $n += $c[$i] * $b[$i++]);
        if ($c[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) return false;
        return true;
    }

    protected function validatePhone($p)
    {
        $digits = preg_replace('/\D/', '', $p);
        return strlen($digits) === 12 || strlen($digits) === 13;
    }

    protected function validateRandom($r)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($r));
    }
}
