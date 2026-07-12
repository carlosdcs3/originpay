<?php

namespace App\Services\Cms;

use Illuminate\Http\UploadedFile;

class CustomLandingSecurity
{
    /** @return list<string> */
    public function validateArchive(UploadedFile $file): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return ['zip_unavailable'];
        }

        $errors = [];
        $zip = new \ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            return ['invalid_archive'];
        }

        $forbidden = ['php', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'bat', 'cmd', 'exe', 'dll', 'html', 'htm', 'svg'];
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            $normalized = str_replace('\\', '/', $name);
            if (str_starts_with($normalized, '/') || preg_match('/(^|\/)\.\.($|\/)/', $normalized)) {
                $errors[] = 'unsafe_path';
            }
            if (in_array(strtolower(pathinfo($normalized, PATHINFO_EXTENSION)), $forbidden, true)) {
                $errors[] = 'forbidden_extension';
            }
        }
        $zip->close();

        return array_values(array_unique($errors));
    }

    public function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|math)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#<(script|style|iframe|object|embed|svg|math)\b[^>]*/?>#is', '', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*(javascript|data):.*?\2/i', '', $html) ?? '';

        return strip_tags($html, '<div><section><main><header><footer><nav><p><br><span><strong><em><b><i><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tr><th><td><blockquote><code><pre>');
    }
}
