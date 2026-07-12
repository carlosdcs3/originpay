<?php

namespace Tests\Feature;

use App\Services\Cms\CustomLandingSecurity;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class R7CmsSecurityTest extends TestCase
{
    public function test_html_sanitizer_blocks_active_content_and_javascript_urls(): void
    {
        $html = '<div onclick="steal()"><script>alert(1)</script><a href="javascript:alert(1)">x</a><p>safe</p></div>';
        $safe = app(CustomLandingSecurity::class)->sanitizeHtml($html);

        $this->assertStringNotContainsStringIgnoringCase('<script', $safe);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $safe);
        $this->assertStringNotContainsStringIgnoringCase('javascript:', $safe);
        $this->assertStringContainsString('<p>safe</p>', $safe);
    }

    public function test_zip_rejects_path_traversal_executable_html_and_svg_entries(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive indisponível.');
        }

        $path = tempnam(sys_get_temp_dir(), 'r7zip');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('../escape.php', '<?php');
        $zip->addFromString('image.svg', '<svg onload="alert(1)"/>');
        $zip->close();

        $file = new UploadedFile($path, 'landing.zip', 'application/zip', null, true);
        $errors = app(CustomLandingSecurity::class)->validateArchive($file);

        $this->assertContains('unsafe_path', $errors);
        $this->assertContains('forbidden_extension', $errors);
        @unlink($path);
    }
}
