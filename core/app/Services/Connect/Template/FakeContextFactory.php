<?php
namespace App\Services\Connect\Template;

use App\Support\Connect\TemplateVariableRegistry;

class FakeContextFactory
{
    public function generate(): array
    {
        $vars = TemplateVariableRegistry::all();
        $context = [];
        foreach ($vars as $key => $def) {
            $context[$key] = $def->example;
        }
        return $context;
    }
}
