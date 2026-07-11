<?php
namespace App\Services\Connect\Template\Validators;
use Illuminate\Validation\ValidationException;

class SchemaValidator
{
    public function validate(array $ast): void
    {
        if (!isset($ast['version']) || !isset($ast['blocks']) || !is_array($ast['blocks'])) {
            throw ValidationException::withMessages(['ast' => 'Formato JSON da AST inválido. Versão e array de blocks são obrigatórios.']);
        }
    }
}
