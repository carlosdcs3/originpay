<?php
namespace App\Services\Connect\Template\Renderers;

interface TemplateChannelRenderer
{
    /**
     * Renders an AST structure into a specific channel format.
     */
    public function render(array $ast, array $context): string;
}
