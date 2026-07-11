<?php
namespace App\Services\Connect\Template\Validators;
use Illuminate\Validation\ValidationException;

class BlockValidator
{
    protected $allowedTypes = ['heading', 'paragraph', 'button', 'image', 'divider'];

    public function validate(array $ast): void
    {
        foreach ($ast['blocks'] as $idx => $block) {
            if (!isset($block['id'])) {
                throw ValidationException::withMessages(['ast' => "Bloco [{$idx}] requer um ID único."]);
            }
            if (!isset($block['type']) || !in_array($block['type'], $this->allowedTypes)) {
                throw ValidationException::withMessages(['ast' => "Bloco [{$idx}] possui um type inválido ou desconhecido."]);
            }
        }
    }
}
