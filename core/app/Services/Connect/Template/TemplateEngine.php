<?php
namespace App\Services\Connect\Template;

use App\Services\Connect\Template\Renderers\TemplateChannelRenderer;
use App\Services\Connect\Template\Renderers\EmailRenderer;
use App\Services\Connect\Template\Renderers\WhatsappRenderer;
use App\Services\Connect\Template\Validators\SchemaValidator;
use App\Services\Connect\Template\Validators\BlockValidator;
use App\Services\Connect\Template\Validators\SecurityValidator;
use Exception;

class TemplateEngine
{
    protected $cache = []; // Memory cache

    public function validate(array $ast): void
    {
        (new SchemaValidator())->validate($ast);
        (new BlockValidator())->validate($ast);
        (new SecurityValidator())->validate($ast);
        // Variable Validator could be added here
    }

    public function compile(array $ast, string $channel, array $context): string
    {
        // Simple memory caching via md5 of AST + Context + Channel
        $hash = md5(json_encode($ast) . $channel . json_encode($context));
        
        if (isset($this->cache[$hash])) {
            return $this->cache[$hash];
        }

        $renderer = $this->getRenderer($channel);
        $output = $renderer->render($ast, $context);
        
        $this->cache[$hash] = $output;

        return $output;
    }

    protected function getRenderer(string $channel): TemplateChannelRenderer
    {
        return match (strtolower($channel)) {
            'email' => new EmailRenderer(),
            'whatsapp' => new WhatsappRenderer(),
            default => throw new Exception("Canal {$channel} não possui Renderer implementado."),
        };
    }
}
