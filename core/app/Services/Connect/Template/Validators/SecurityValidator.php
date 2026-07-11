<?php
namespace App\Services\Connect\Template\Validators;
use Illuminate\Validation\ValidationException;

class SecurityValidator
{
    public function validate(array $ast): void
    {
        $json = json_encode($ast);
        if (preg_match('/<\?php/i', $json) || preg_match('/<script/i', $json) || preg_match('/@foreach/i', $json)) {
            throw ValidationException::withMessages(['ast' => 'Conteúdo malicioso detectado. Scripts, PHP ou Blade não são permitidos.']);
        }
    }
}
