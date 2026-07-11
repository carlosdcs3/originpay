<?php
namespace App\Services\Connect\Template\Renderers;

class EmailRenderer implements TemplateChannelRenderer
{
    public function render(array $ast, array $context): string
    {
        $html = '<div class="email-container" style="font-family: Arial, sans-serif; line-height: 1.6;">';
        $blocks = $ast['blocks'] ?? [];
        
        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'paragraph';
            $content = $this->replaceVariables($block['content'] ?? '', $context);
            
            switch ($type) {
                case 'heading':
                    $html .= "<h2>{$content}</h2>";
                    break;
                case 'paragraph':
                    $html .= "<p>{$content}</p>";
                    break;
                case 'button':
                    $url = $this->replaceVariables($block['url'] ?? '#', $context);
                    $label = $this->replaceVariables($block['label'] ?? 'Clique Aqui', $context);
                    $html .= "<a href='{$url}' style='display:inline-block; padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:5px;'>{$label}</a>";
                    break;
                default:
                    $html .= "<div>{$content}</div>";
                    break;
            }
        }
        $html .= '</div>';
        
        return $html;
    }

    protected function replaceVariables(string $text, array $context): string
    {
        return preg_replace_callback('/\{\{([a-zA-Z0-9_.]+)\}\}/', function($matches) use ($context) {
            $key = trim($matches[1]);
            return $context[$key] ?? $matches[0];
        }, $text);
    }
}
