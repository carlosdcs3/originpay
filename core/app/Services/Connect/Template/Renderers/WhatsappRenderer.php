<?php
namespace App\Services\Connect\Template\Renderers;

class WhatsappRenderer implements TemplateChannelRenderer
{
    public function render(array $ast, array $context): string
    {
        $text = '';
        $blocks = $ast['blocks'] ?? [];
        
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'paragraph';
            $content = $this->replaceVariables($block['content'] ?? '', $context);
            
            switch ($type) {
                case 'heading':
                    $text .= "*{$content}*\n\n";
                    break;
                case 'paragraph':
                    $text .= "{$content}\n\n";
                    break;
                case 'button':
                    $url = $this->replaceVariables($block['url'] ?? '#', $context);
                    $label = $this->replaceVariables($block['label'] ?? 'Link', $context);
                    $text .= "🔗 {$label}: {$url}\n\n";
                    break;
                default:
                    $text .= "{$content}\n\n";
                    break;
            }
        }
        
        return trim($text);
    }

    protected function replaceVariables(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($matches) use ($context) {
            $key = trim($matches[1]);
            return $context[$key] ?? $matches[0];
        }, $text);
    }
}
