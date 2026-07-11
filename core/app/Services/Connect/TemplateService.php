<?php
namespace App\Services\Connect;

use App\Models\Connect\Template;
use App\Services\Connect\Template\TemplateEngine;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TemplateService
{
    protected $engine;

    public function __construct(TemplateEngine $engine)
    {
        $this->engine = $engine;
    }

    public function createTemplate(array $data, $merchantId, $userId)
    {
        $ast = is_string($data['content']) ? json_decode($data['content'], true) : $data['content'];
        $this->engine->validate($ast);

        return Template::create([
            'uuid' => Str::uuid()->toString(),
            'merchant_id' => $merchantId,
            'channel' => $data['channel'],
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'content' => $ast,
            'status' => Template::STATUS_DRAFT,
            'is_current' => true,
            'version' => 1,
            'created_by' => $userId,
        ]);
    }

    public function updateTemplate($id, array $data, $merchantId, $userId)
    {
        $template = Template::forMerchant($merchantId)->findOrFail($id);
        $ast = is_string($data['content']) ? json_decode($data['content'], true) : $data['content'];
        $this->engine->validate($ast);

        if ($template->status === Template::STATUS_PUBLISHED) {
            return DB::transaction(function () use ($template, $data, $ast, $userId) {
                // Remove current flag from old
                $template->update(['is_current' => false]);
                
                // Create new draft version
                return Template::create([
                    'uuid' => Str::uuid()->toString(),
                    'merchant_id' => $template->merchant_id,
                    'parent_template_id' => $template->parent_template_id ?? $template->id,
                    'channel' => $template->channel,
                    'name' => $data['name'],
                    'subject' => $data['subject'] ?? null,
                    'content' => $ast,
                    'status' => Template::STATUS_DRAFT,
                    'is_current' => true,
                    'version' => $template->version + 1,
                    'created_by' => $userId,
                ]);
            });
        }

        // Just update if it's draft
        $template->update([
            'name' => $data['name'],
            'subject' => $data['subject'] ?? null,
            'content' => $ast,
            'updated_by' => $userId,
        ]);

        return $template;
    }

    public function publishTemplate($id, $merchantId, $userId)
    {
        $template = Template::forMerchant($merchantId)->findOrFail($id);
        $template->update([
            'status' => Template::STATUS_PUBLISHED,
            'published_at' => now(),
            'updated_by' => $userId,
        ]);
        return $template;
    }
}
