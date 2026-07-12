<?php

namespace App\Support\Observability;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

trait CarriesOperationalContext
{
    /**
     * @var array<string, mixed>
     */
    public array $operationalContext = [];

    /**
     * @param  array<string, mixed>  $context
     */
    protected function captureOperationalContext(array $context = []): void
    {
        $base = [];

        foreach (['correlation_id', 'tenant_id', 'merchant_id', 'user_id', 'api_key_id', 'payment_id', 'gateway', 'webhook_event_id'] as $key) {
            $value = Context::get($key);
            if ($value !== null && $value !== '') {
                $base[$key] = $value;
            }
        }

        $base = array_merge($base, $context);
        $base['correlation_id'] = isset($base['correlation_id']) && Str::isUuid((string) $base['correlation_id'])
            ? (string) $base['correlation_id']
            : (string) Str::uuid();
        $base['job_id'] = $base['job_id'] ?? (string) Str::uuid();

        $this->operationalContext = app(StructuredLogContext::class)->withoutEmpty($base);
    }

    /**
     * @return array<string, mixed>
     */
    public function operationalContext(): array
    {
        if ($this->operationalContext === []) {
            $this->captureOperationalContext();
        }

        return $this->operationalContext;
    }
}
