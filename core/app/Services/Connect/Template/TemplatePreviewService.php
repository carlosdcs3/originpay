<?php
namespace App\Services\Connect\Template;

class TemplatePreviewService
{
    protected $engine;
    protected $fakeFactory;

    public function __construct(TemplateEngine $engine, FakeContextFactory $fakeFactory)
    {
        $this->engine = $engine;
        $this->fakeFactory = $fakeFactory;
    }

    public function generatePreview(array $ast, string $channel): string
    {
        $context = $this->fakeFactory->generate();
        return $this->engine->compile($ast, $channel, $context);
    }
}
