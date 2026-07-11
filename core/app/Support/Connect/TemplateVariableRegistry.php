<?php
namespace App\Support\Connect;

class TemplateVariableRegistry
{
    protected static $variables = [];

    public static function boot()
    {
        if (!empty(self::$variables)) return;

        self::$variables = [
            'contact.name' => new VariableDefinition('contact.name', 'Nome do Contato', 'Nome completo do contato', 'João Silva', 'Contato'),
            'contact.email' => new VariableDefinition('contact.email', 'Email do Contato', 'Endereço de email do contato', 'joao@email.com', 'Contato'),
            'merchant.name' => new VariableDefinition('merchant.name', 'Nome da Loja', 'Nome do Lojista', 'Minha Loja', 'Empresa'),
            'merchant.portal_url' => new VariableDefinition('merchant.portal_url', 'URL do Portal', 'Link principal da loja', 'https://minhaloja.com', 'Empresa'),
        ];
    }

    public static function all(): array
    {
        self::boot();
        return self::$variables;
    }
}
